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
use App\Rules\ValidatorParamsRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StaffController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    protected array $nullableString;
    protected array $requiredString;
    protected array $dateBeforeToday;
    protected array $dateAfterToday;
    protected array $dateAfterOrEqualToday;
    protected array $nullableImage;
    protected array $requiredImage;

    public function __construct()
    {
        $this->nullableString = ValidatorParamsRule::nullableString();
        $this->requiredString = ValidatorParamsRule::requiredString();
        $this->dateBeforeToday = ValidatorParamsRule::dateBeforeToday();
        $this->dateAfterToday = ValidatorParamsRule::dateAfterToday();
        $this->dateAfterOrEqualToday = ValidatorParamsRule::dateAfterOrEqualToday();
        $this->nullableImage = ValidatorParamsRule::nullableImage();
        $this->requiredImage = ValidatorParamsRule::requiredImage();
    }
    public function index(request $request)
    {
        $query = User::query()
            ->where('role', 'staff')
            ->orWhere('role', 'admin')
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
      

        $data = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],

            'first_name' => ['max:255', ...$this->requiredString],
            'last_name' => ['max:255', ...$this->requiredString],
            'middle_name' => ['max:255', ...$this->nullableString],
            'address' => ['max:500', ...$this->nullableString],
            'city' => ['max:100', ...$this->nullableString],
            'province' => ['max:100', ...$this->nullableString],
            'gender' => ['max:10', ...$this->nullableString],
            'postal_code' => ['max:20', ...$this->nullableString],
            'phone' => ['max:20', ...$this->nullableString],
            'id_type' => ['max:50', ...$this->nullableString],
            'id_number' => ['max:100', ...$this->nullableString],
            'photo' => ['max:5120', ...$this->nullableImage],
            'role' => ['max:50', ...$this->nullableString],
            'department' => ['max:100', ...$this->nullableString],
            'birthday' => [...$this->dateBeforeToday],
        ]);

        if ($request->hasFile('photo')) {
            $data['profile_photo_path'] = $request->file('photo')->store('staff', 'public');
        }

        $data['date_of_birth'] = $data['birthday'] ?? null;
        $data['job_title'] = $data['role'] ?? null;

        $user = User::create([
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'staff',
        ]);

        $userData = Arr::except($data, [
            'email',
            'password',
            'birthday',
            'role',
            'photo',
        ]);

        $userData['user'] = $user->id;

        UserData::create($userData);

        return redirect()
            ->route('staff.index')
            ->with('success', 'Staff created successfully.');
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

        $data = $request->validate([
            'first_name' => ['max:255', ...$this->requiredString],
            'last_name' => ['max:255', ...$this->requiredString],
            'middle_name' => ['max:255', ...$this->nullableString],
            'address' => ['max:500', ...$this->nullableString],
            'city' => ['max:100', ...$this->nullableString],
            'province' => ['max:100', ...$this->nullableString],
            'gender' => ['max:10', ...$this->nullableString],
            'postal_code' => ['max:20', ...$this->nullableString],
            'phone' => ['max:20', ...$this->nullableString],
            'id_type' => ['max:50', ...$this->nullableString],
            'id_number' => ['max:100', ...$this->nullableString],
            'photo' => ['max:5120', ...$this->nullableImage],
            'role' => ['max:50', ...$this->nullableString],
            'department' => ['max:100', ...$this->nullableString],
            'birthday' => [...$this->dateBeforeToday],
            'employment_status' => ['required', Rule::in(['Active', 'Inactive'])],
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
        
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
