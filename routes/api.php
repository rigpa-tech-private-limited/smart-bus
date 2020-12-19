<?php

use App\Http\Controllers\DriverAppApiController;
use App\Http\Controllers\RestAPIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('device_activation', [RestAPIController::class, 'deviceActivation']);
Route::post('get_otp', [DriverAppApiController::class, 'getOTP']);
Route::post('verify_otp', [DriverAppApiController::class, 'verifyOTP']);
Route::post('get_trips', [DriverAppApiController::class, 'getTrips']);
Route::post('start_trip', [DriverAppApiController::class, 'startTrip']);
Route::post('close_trip', [DriverAppApiController::class, 'closeTrip']);
