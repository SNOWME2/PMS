<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});
Route::middleware(['auth'])->group(function () {

    Route::get('/admin/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('role:admin');

    Route::get('/staff/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('role:staff');

    Route::get('/tenant/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('role:tenant');
});