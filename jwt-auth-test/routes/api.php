<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailValidationController;


Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

//POR TESTAR
// Route::middleware(['auth:api', 'permission:view profile'])
//     ->get('/profile-secure', function () {
//         return response()->json(['user' => auth('api')->user()]);
//     });
Route::middleware(['auth:api'])
    ->get('/profile-secure', [AuthController::class,'profile']);

Route::post('/validate-email', [EmailValidationController::class, 'sendToken']);
Route::post('/verify-email-token', [EmailValidationController::class, 'verifyToken']);
Route::post('/verify-2fa', [AuthController::class, 'verify2FA']);

