<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\UnitsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\LeasesController;
use App\Http\Controllers\TenantsController;
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
Route::get('/speed-test', function () {
    return response()->json([
        'message' => 'OK',
        'time' => microtime(true),
    ]);
});
Route::middleware(['auth'])->group(function () {


    // ── Properties ────────────────────────────────────────────────────────────
    // GET    /properties              → PropertyController@index
    // GET    /properties/create       → PropertyController@create
    // POST   /properties              → PropertyController@store
    // GET    /properties/{property}   → PropertyController@show
    // GET    /properties/{property}/edit → PropertyController@edit
    // PUT    /properties/{property}   → PropertyController@update
    // DELETE /properties/{property}   → PropertyController@destroy
    Route::resource('properties', PropertiesController::class);

    // ── Units ─────────────────────────────────────────────────────────────────
    // GET    /units/create            → UnitController@create   (?property_id=X)
    // POST   /units                   → UnitController@store
    // GET    /units/{unit}            → UnitController@show
    // GET    /units/{unit}/edit       → UnitController@edit
    // PUT    /units/{unit}            → UnitController@update
    // DELETE /units/{unit}            → UnitController@destroy
    Route::resource('units', UnitsController::class);
    

    Route::get('/tenants', function (){
        return Inertia::render('Tenants/Index');
    });


    Route::resource('staffs', StaffController::class);

    Route::resource('tenants', TenantsController::class);
    Route::resource('leases', LeasesController::class);
    Route::post('leases/{lease}/terminate', [LeasesController::class, 'terminate'])->name('leases.terminate');
    Route::post('leases/{lease}/renew', [LeasesController::class, 'renew'])->name('leases.renew');
});