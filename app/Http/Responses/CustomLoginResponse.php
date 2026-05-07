<?php

namespace App\Http\Responses;


use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class CustomLoginResponse implements  LoginResponseContract
{
    public function toResponse($request)
    {
        $user = $request->user();

        return match ($user?->role) {
            'admin' => redirect('/admin/dashboard'),
            'staff' => redirect('/staff/dashboard'),
            'tenant' => redirect('/tenant/dashboard'),
            default => redirect('/'),
        };
    }
}
