<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;

// use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    public function getUser()
    {
        try {
            // これはユーサーの情報
            $user = JWTAuth::parseToken()->authenticate();
    
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'User fetched successfully',
                'data' => $user
            ]);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => 'Token has expired',
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 401,
                'message' => 'Token is invalid',
            ], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 400,
                'message' => 'Token is absent',
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            // AUTHENTICATE USER
            $user = JWTAuth::parseToken()->authenticate();

            // VALIDATE DATA
            $request->validate([
                'name' => 'nullable|string',
                'username' => 'nullable|string|min:5|max:15',
                'email' => 'nullable',
                'bio' => 'nullable',
                'old_password' => 'nullable',
                'new_password' => [
                    'nullable',
                    'min:8',
                    'regex:/^(?=.\d)(?=.[@$!%?&_\\-])[A-Za-z\d@$!%?&_\\-]+$/',
                ],
                'avatar' => 'nullable',
            ], [
                'username.unique' => 'Username already exists',
                'username.max' => 'Username must be between 5 and 15 characters',
                'username.min' => 'Username must be between 5 and 15 characters',
                'password.min' => 'Password must be at least 8 characters',
                'password.regex' => 'Password must contain at least one number and one special character',
            ]);

            try {

                // PROFILE DATA CHANGE
                if ($request->has('name')) {
                    $user->name = $request->get('name');
                }
                if ($request->has('username')) {
                    $user->username = $request->get('username');
                }
                if ($request->has('email')) {
                    $user->email = $request->get('email');
                }
                if ($request->has('bio')) {
                    $user->bio = $request->get('bio');
                }

                // PASSWORD CHANGE
                if ($request->filled('old_password', 'new_password')) {
                    if (!Hash::check($request->old_password, $user->password)) {
                        return response()->json([
                            'status' => 'error',
                            'code' => 400,
                            'message' => 'Old password is incorrect',
                        ], 400);
                    }
                    $user->password = Hash::make($request->get('new_password'));
                }

                $isUpdate = $request->query('is_update');

                // AVATAR CHANGE
                if ($isUpdate == 'true') {
                    $user->avatar = $request->input('avatar.data');
                }

                // SAVE THE UPDATED DATA
                $user->save();

                // RETURN RESPONSE
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Profile updated successfully 2',
                    'data' => $request->all()
                ]);
            } catch (\Exception $th) {
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $th->getMessage(),
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

    public function addProfilePicture(Request $request)
    {
        try {

            $request->validate([
                'avatar' => 'required|file|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            try {
                $user = JWTAuth::parseToken()->authenticate();

                // Handle file upload
                if ($request->hasFile('avatar')) {

                    if ($user->avatar) {
                        Storage::disk('public')->delete($user->avatar);
                    }

                    $imagePath = $request->file('avatar')->store('avatar', 'public');
                    // $user->avatar = $imagePath;
                    // $user->save(); //ini garis merah biarin aja tetep mau jalan beliau

                    return response()->json([
                        'status' => 'success',
                        'code' => 201,
                        'message' => 'Profile image updated successfully.',
                        'data' => $imagePath
                    ], 201);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'An error occurred while updating the profile picture',
                    'error' => $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $err) {
            return response()->json([
                'message' => $err->getMessage()
            ]);
        }
    }

    // 下のファンクションは多分使いません

    public function getProfile($user_id)
    {
        try {
            $userData = User::find($user_id);

            if (!$userData) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'User not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'User data retrieved successfully',
                'data' => $userData
            ]);

        } catch (\Exception $th) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $request->validate([
                'old_password' => 'required',
                'password' => [
                    'required',
                    'min:8',
                    'regex:/^(?=.*\d)(?=.*[@$!%*?&_\\-])[A-Za-z\d@$!%*?&_\\-]+$/',
                ]
            ]);
    
            try {
                $user = User::find($request->user()->id);
    
                if (!Hash::check($request->old_password, $user->password)) {
                    return response()->json([
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'Old password is incorrect',
                    ], 400);
                }
    
                $user->password = Hash::make($request->input('password'));
                $user->save();
    
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Password updated successfully',
                    'data' => $user
                ]);
    
            } catch (\Exception $err) {
                return response()->json([
                    'status' => 'error',
                    'code' => 500,
                    'message' => $err->getMessage(), 
                ], 500);
            }
        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $err->getMessage(),
            ], 500);
        }
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'sometimes|string',
            'username' => 'sometimes|string|min:5|max:15',
            // 'email' => 'sometimes',
            'bio' => 'sometimes',
        ]);

        try {
            $user = User::find($request->user()->id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'User not found',
                ], 404);
            }

            if ($request->has('name')) {
                $user->name = $request->get('name');
            }

            if ($request->has('username')) {
                $user->username = $request->get('username');
            }

            // if ($request->has('email')) {
            //     $user->email = $request->get('email');
            // }

            if ($request->has('bio')) {
                $user->bio = $request->get('bio');
            }

            $user->save();

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Profile updated successfully',
                'data' => $user
            ]);

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'error',
                'code' => 500,
                'message' => $err->getMessage(),
            ], 500);
        }
    }
    
}
