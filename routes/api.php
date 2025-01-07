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
    Route::post('/logout', [AuthController::class, 'logout']);

    // USER
    Route::put('/user/update', [UserController::class, 'update']);
    Route::get('/user/user-profile', [UserController::class, 'getUser']);
    Route::post('/user/update-picture', [UserController::class, 'addProfilePicture']);

    // STORY
    Route::post('/story/create', [StoryController::class, 'createStory']);
    
    

    // optional
    Route::put('/user/change-password', [UserController::class, 'updatePassword']);
    Route::get('/user/profile/{user_id}', [UserController::class, 'getProfile']);
});

// STORY
Route::get('/stories', [StoryController::class, 'getAllStories']);
Route::get('/story/{story_id}', [StoryController::class, 'getStoryById']);
Route::get('/categories', [StoryController::class, 'getAllCategories']);
