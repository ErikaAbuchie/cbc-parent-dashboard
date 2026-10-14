<?php

use App\Http\Controllers\TeacherAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/teacher/login', [TeacherAuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/teacher/logout', [TeacherAuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return response()->json(['message' => 'Laravel is alive']);
});
