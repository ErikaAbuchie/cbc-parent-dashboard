<?php

use App\Http\Controllers\TeacherAuthController;
use App\Http\Controllers\TeacherAssessmentController;
use App\Http\Controllers\TeacherStudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/teacher/login', [TeacherAuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/teacher/logout', [TeacherAuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('teacher')->group(function () {
    Route::get('/classes/{classId}/students', [TeacherStudentController::class, 'index'])->whereNumber('classId');
    Route::post('/classes/{classId}/students', [TeacherStudentController::class, 'store'])->whereNumber('classId');
    Route::patch('/classes/{classId}/students/{studentId}', [TeacherStudentController::class, 'update'])->whereNumber(['classId', 'studentId']);
    Route::delete('/classes/{classId}/students/{studentId}', [TeacherStudentController::class, 'destroy'])->whereNumber(['classId', 'studentId']);
    Route::post('/classes/{classId}/students/{studentId}/restore', [TeacherStudentController::class, 'restore'])->whereNumber(['classId', 'studentId']);
    Route::get('/classes', [TeacherAssessmentController::class, 'classes']);
    Route::get('/classes/{classId}/assessment-options', [TeacherAssessmentController::class, 'options'])->whereNumber('classId');
    Route::get('/classes/{classId}/assessments', [TeacherAssessmentController::class, 'index'])->whereNumber('classId');
    Route::get('/classes/{classId}/assessments/{assessmentId}/history', [TeacherAssessmentController::class, 'history'])->whereNumber(['classId', 'assessmentId']);
    Route::post('/classes/{classId}/assessments', [TeacherAssessmentController::class, 'store'])->whereNumber('classId');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', function () {
    return response()->json(['message' => 'Laravel is alive']);
});
