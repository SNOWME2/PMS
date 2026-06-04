<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserData;

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
            $query->where(
                fn($q) => $q
                    ->where('first_name', 'like', "{$search}%")
                    ->orWhere('last_name', 'like', "{$search}%")
                    ->orWhere('middle_name', 'like', "{$search}%")
                    ->orWhere('id', 'like', "{$search}%")
               
            );
        }
        $staffs = $query->paginate(12); 
   
        $staffs->getCollection()->transform(fn($s) => [
            'id'         => $s->id,
            'name'       => $s->userData?->full_name,
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
