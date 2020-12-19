<?php

use App\Http\Controllers\RestAPIController;
use Illuminate\Http\Request;
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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('device_activation', [RestAPIController::class, 'deviceActivation']);
Route::post('get_otp', [RestAPIController::class, 'getOTP']);
Route::post('verify_otp', [RestAPIController::class, 'verifyOTP']);
Route::post('get_trips', [RestAPIController::class, 'getTrips']);
Route::post('start_trip', [RestAPIController::class, 'startTrip']);
Route::post('close_trip', [RestAPIController::class, 'closeTrip']);
