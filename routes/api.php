<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChildController;
use App\Http\Controllers\Api\VaccinationController;
use App\Http\Controllers\Api\HealthWorkerDashboardController;


/*
|--------------------------------------------------------------------------
| API Health Check
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'BirthGuard Laravel API is running.',
        'service' => 'birthguard-api',
    ]);
});


/*
|--------------------------------------------------------------------------
| API Version 1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Public Authentication Routes
    |--------------------------------------------------------------------------
    */

    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);


    /*
    |--------------------------------------------------------------------------
    | Protected Routes
    |--------------------------------------------------------------------------
    */

    Route::middleware('auth:sanctum')->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);


        /*
        |--------------------------------------------------------------------------
        | Health Worker Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/health-worker/dashboard',
            [HealthWorkerDashboardController::class, 'index']
        );

        Route::get(
            '/health-worker/upcoming-vaccinations',
            [VaccinationController::class, 'healthWorkerUpcoming']
        );


        /*
        |--------------------------------------------------------------------------
        | Child / Birth Registration
        |--------------------------------------------------------------------------
        */

        Route::post('/children', [ChildController::class, 'store']);
        Route::get('/children', [ChildController::class, 'index']);
        Route::get('/children/{child}', [ChildController::class, 'show']);
        Route::put('/children/{child}', [ChildController::class, 'update']);
        Route::delete('/children/{child}', [ChildController::class, 'destroy']);


        /*
        |--------------------------------------------------------------------------
        | Child Vaccinations
        |--------------------------------------------------------------------------
        */

        Route::apiResource(
            'children.vaccinations',
            VaccinationController::class
        )->except(['create', 'edit']);


        /*
        |--------------------------------------------------------------------------
        | Mark Vaccination As Administered
        |--------------------------------------------------------------------------
        */

        Route::patch(
            '/children/{child}/vaccinations/{vaccination}/administer',
            [VaccinationController::class, 'markAdministered']
        );

    });

});