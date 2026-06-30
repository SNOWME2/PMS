<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserData;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;    
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
            $query->whereHas('userData',
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
            'photo'      => $s->profile_photo_path,
            'phone'      => $s->userData?->phone,
            'birthday'   => $s->userData?->date_of_birth,
            'gender'     => $s->userData?->gender,
            'job_title'  => $s->userData?->job_title,
            'department' => $s->userData?->department,
            'initials'   => $s->userData?->initials,
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
        //
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
    public function update(Request $request, string $id)
    {

        try {

            $userId = $id;
            User::findOrFail($userId);
            $UserData = UserData::where('user', $userId)->first();


            // Log::info('Updating user data for user ID: ' . $request);
            $data = $request->validate([
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'middle_name' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:500'],
                'city' => ['nullable', 'string', 'max:100'],
                'province' => ['nullable', 'string', 'max:100'],
                'postal_code' => ['nullable', 'string', 'max:20'],
                'phone' => ['nullable', 'string', 'max:20'],
                'id_type' => ['nullable', 'string', 'max:50'],
                'id_number' => ['nullable', 'string', 'max:100'],
                'date_of_birth' => ['nullable', 'date'],
                'role' => ['nullable', 'string', 'max:50'],
            ]);

            //Note: UserData is a separate model that holds additional information about the user, such as address, phone number, etc. We check if a UserData record exists for the given user. If it does, we update it; if not, we create a new record.
            if ($UserData) {
                // Update existing UserData record
                $UserData->update($data);
            } else {
                // Create a new UserData record if it doesn't exist
                $data['user'] = $userId;
                UserData::create($data);
            }
            
        } catch (ModelNotFoundException $e) {

            return back()->withErrors([
                'error' => 'User not found.'
            ]);

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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
