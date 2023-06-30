<?php

use App\Http\Controllers\ChapterController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ImageCourseController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MentorController;
use App\Http\Controllers\MyCourseController;
use App\Http\Controllers\ReviewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::resource('mentors', MentorController::class);
Route::resource('courses', CourseController::class);
Route::resource('chapters', ChapterController::class);
Route::resource('lessons', LessonController::class);
Route::resource('image-courses', ImageCourseController::class);
Route::resource('my-courses', MyCourseController::class);
Route::post('my-courses/premium', [MyCourseController::class, 'createPremiumAccess']);
Route::resource('reviews', ReviewController::class);
