<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(request $request)
    {
        $query = User::query()
            ->where('role', 'staff')
            ->with('userData');


        if ($search = $request->input('search')) {
            $query->whereHas(
                'userData',
                fn($userData) =>
                $userData->where('first_name', 'like', "{$search}%")
                    ->orWhere('middle_name', 'like', "{$search}%")
                    ->orWhere('last_name', 'like', "{$search}%")
            );
        }
        $staffs = $query->paginate(12);

        $staffs->getCollection()->transform(fn($s) => [
            'id'         => $s->id,
            'name'       => $s->userData?->full_name,
            'first_name' => $s->userData?->first_name,
            'middle_name' => $s->userData?->middle_name,
            'last_name'  => $s->userData?->last_name,
            'email'      => $s->email,
            'address'    => $s->userData?->address,
            'city'       => $s->userData?->city,
            'province'   => $s->userData?->province,
            'photo'      => $s->userData?->profile_photo_path,
            'phone'      => $s->userData?->phone,
            'birthday'   => $s->userData?->date_of_birth,
            'gender'     => $s->userData?->gender,
            'job_title'  => $s->userData?->job_title,
            'department' => $s->userData?->department,
            'initials'   => $s->userData?->initials,
            'employment_status' => $s->userData?->employment_status,
            'status'     => $s->userData?->status,
            'last_login' => $s->userData?->last_login,
         
            
        ]);

        return inertia('Staffs/Index', [
            'staff' => $staffs,
            'filters'    => $request->only(['search']),
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            // Log::info('Updating user data for user ID: ' . $request);
            $data = $request->validate([
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],

                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'middle_name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:500'],
                'city' => ['nullable', 'string', 'max:100'],
                'province' => ['nullable', 'string', 'max:100'],
                'gender' => ['nullable', 'string', 'max:10'],
                'postal_code' => ['nullable', 'string', 'max:20'],
                'phone' => ['nullable', 'string', 'max:20'],
                'id_type' => ['nullable', 'string', 'max:50'],
                'id_number' => ['nullable', 'string', 'max:100'],
                'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'role' => ['nullable', 'string', 'max:50'],
                'department' => ['nullable', 'string', 'max:100'],
                'birthday' => ['nullable', 'date'],
            ]);

            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('staff', 'public');
            }
            //Rename birthday to date_of_birth and role to job_title to match the database columns
            $data['profile_photo_path'] = $data['photo'];
            $data['date_of_birth'] = $data['birthday'];
            $data['job_title'] = $data['role'];
            $email = $data['email'];
            $password = $data['password'];

            //Create user Credentials Email and Password
            $user = User::create([
                'email' => $email,
                'password' => Hash::make($password),
                'role' => 'staff',
            ]);

            //Removal of email and password from the data array before creating UserData
            $userData = Arr::except($data, ['email', 'password']);

            //Create UserData record for the newly created user
            $userData['user'] = $user->id;
            UserData::create($userData);

        } catch (QueryException $e) {

            return back()->withErrors([
                'error' => 'Database error. Please try again later.'
            ]);
        } catch (\Exception $e) {

            return back()->withErrors([
                'error' => 'An unexpected error occurred.'
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */

    public function update(Request $request, User $staff)
    {
        try {
            $data = $request->validate([
                'email'       => ['required', 'email', 'max:255'],
                'first_name'  => ['required', 'string', 'max:255'],
                'last_name'   => ['required', 'string', 'max:255'],
                'middle_name' => ['nullable', 'string', 'max:255'],
                'address'     => ['nullable', 'string', 'max:500'],
                'city'        => ['nullable', 'string', 'max:100'],
                'province'    => ['nullable', 'string', 'max:100'],
                'gender'      => ['nullable', 'string', 'max:10'],
                'phone'       => ['nullable', 'string', 'max:20'],
                'department'  => ['nullable', 'string', 'max:100'],
                'role'        => ['nullable', 'string', 'max:50'],
                'birthday'    => ['nullable', 'date'],
                'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
                'employment_status' => ['nullable', 'string', 'max:50'],
            ]);

            // Create UserData if it doesn't exist
            $userData = $staff->userData;

            if (!$userData) {
                $userData = new UserData();
                $userData->user = $staff->id; // or user_id depending on your column name
            }

            if ($request->hasFile('photo')) {

                if ($userData->profile_photo_path) {
                    Storage::disk('public')->delete($userData->profile_photo_path);
                }

                $data['profile_photo_path'] = $request->file('photo')
                    ->store('staff', 'public');
            }

            // Update users table
            $staff->update([
                'email' => $data['email'],
            ]);

            // Map request fields
            $data['date_of_birth'] = $data['birthday'] ?? null;
            $data['job_title'] = $data['role'] ?? null;

            unset(
                $data['birthday'],
                $data['role'],
                $data['photo'],
                $data['email']
            );

            // Fill and save UserData
            $userData->fill($data);
            $userData->save();

            return back()->with('success', 'Employee updated successfully.');
        } catch (\Exception $e) {
            return back()->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
