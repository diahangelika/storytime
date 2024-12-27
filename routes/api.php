<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UserController;
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

Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['jwt.auth', 'jwt.blacklist'])->group(function() {
//         ------ MAIN ROUTES ------

    // AUTH
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('jwt.auth');

    // USER
    Route::put('/user/update', [UserController::class, 'update'])->middleware('jwt.auth');
    Route::get('/user/profile/{user_id}', [UserController::class, 'getProfile'])->middleware('jwt.auth');
    Route::get('/user/user-profile', [UserController::class, 'getUser'])->middleware('jwt.auth');
    Route::post('/user/update-picture', [UserController::class, 'addProfilePicture'])->middleware('jwt.auth');

    // STORY
    Route::get('/stories', [StoryController::class, 'getAllStories']);

    // optional
    Route::put('/user/change-password', [UserController::class, 'updatePassword'])->middleware('jwt.auth');
    Route::put('/user/update-profile', [UserController::class, 'updateProfile'])->middleware('jwt.auth');
});

Route::get('/categories', [StoryController::class, 'getAllCategories']);
