<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertiesController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
});

Route::get('/website', function () {
    return Inertia::render('Website');
});
Route::middleware(['auth'])->group(function () {

    Route::get('/admin/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('role:admin')->name('admin.dashboard');

    Route::get('/staff/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('role:staff')->name('staff.dashboard');

    Route::get('/tenant/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('role:tenant')->name('tenant.dashboard');

    
});

Route::middleware(['auth'])->group(function () {

    // PROPERTIES
    Route::get('/properties', [PropertiesController::class, 'index'])
        ->name('properties.index');

    // UNITS (by property)
    // Route::get('/properties/{property}/units', [UnitController::class, 'index'])
    //     ->name('units.index');
});