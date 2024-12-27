<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // VALIDATE DATA
        $request->validate([
            'name' => 'required',
            'username' => 'required|string|unique:users,username|min:5|max:15',
            'email' => 'required|unique:users,email|',
            'password' => [
                'required',
                'min:8',
                'regex:/^(?=.*\d)(?=.*[@$!%*?&_\\-])[A-Za-z\d@$!%*?&_\\-]+$/',
            ],
        ], [
            'name.required' => 'Name is required',
            'username.required' => 'Username is required',
            'username.unique' => 'Username already exists',
            'username.max' => 'Username must be between 5 and 15 characters',
            'username.min' => 'Username must be between 5 and 15 characters',
            'email.required' => 'Email is required',
            'email.unique' => 'Email already exists',
            'password.required' => 'Password is required',
            'password.min' => 'Password must be at least 8 characters',
            'password.regex' => 'Password must contain at least one number and one special character',
        ]);

        // CREATE USER
        try {
            $user = User::create([
                'name' => $request->input('name'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
            ]);

            // RETURN RESPONSE
            return response()->json([
                'status' => 'success',
                'code' => 201,
                'message' => 'Registration successful',
                // 'data' => $user, //(optional)
            ]);

        } catch (\Throwable $err) {
            return response()->json([
                'status' => 'failed',
                'code' => 500,
                'message' => $err->getMessage(),
            ], 500);
        }
    }

    public function login(Request $request)
    {
        // VALIDATE LOGIN USER DATA
        $request->validate([
            'email_or_username' => 'required',
            'password' => 'required',
        ]);

        try {
            // PREPARE LOGIN FIELD IS EMAIL OR USERNAME
            $loginField = filter_var($request->email_or_username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            // SAVE THE REQUEST INTO VARIABLE
            $credentials = [
                $loginField => $request->email_or_username,
                'password' => $request->password
            ];

            // USING LARAVEL DEFAULT TOKEN
            // FIND USER BY ID
            // $user = User::where($loginField, $request->email_or_username)->first();

            // $token = $user->createToken('token')->plainTextToken;

            // return response()->json([
            //     'status' => 'success',
            //     'code' => 200,
            //     'message' => 'Login successful',
            //     'token' => $token
            // ]);

            // USING JWT
            try {
                // CHECK IF TOKEN EXISTS
                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json([
                        'status' => 'failed',
                        'code' => 401,
                        'message' => 'Unauthorized',
                    ], 401);
                }

                // FIND USER BY ID
                $user = User::where($loginField, $request->email_or_username)->first();

                // SET DATA THAT CLAIMED FOR THE TOKEN
                $customClaims = [
                    'id' => $user->id,
                    // 'name' => $user->name,
                    // 'username' => $user->username,
                    // 'email' => $user->email, 
                ];

                // GENERATE TOKEN
                $token = JWTAuth::claims($customClaims)->attempt($credentials);

                // RETURN RESPONSE
                return response()->json([
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Login successful',
                    'token' => $token,
                    'user' => $user
                ]);

            } catch (\Exception $err) {
                return response()->json([
                    'status' => 'failed',
                    'code' => 500,
                    'message' => $err->getMessage(),
                ], 500);
            }

        } catch (\Exception $err) {
            return response()->json([
                'status' => 'failed',
                'code' => 500,
                'message' => $err->getMessage(),
            ], 500);
        }      
    }

    public function logout()
    {
        // Mendapatkan token dari header permintaan
        $token = JWTAuth::getToken();

        if ($token) {
            try {
                // Mendapatkan pengguna yang terkait dengan token
                $user = JWTAuth::parseToken()->authenticate();

                // Blacklist token
                Cache::add('jwt_blacklist_' . $token, true, config('jwt.ttl'));

                // Logout pengguna dari aplikasi
                Auth::logout();

                return response()->json(['message' => 'Successfully logged out', 'status' => 'success'], 200);
            } catch (\Exception $e) {
                // Tangani kesalahan saat mencoba untuk mendapatkan pengguna atau memblacklist token
                return response()->json(['error' => 'Failed to logout'], 500);
            }
        }

        return response()->json(['message' => 'No token provided', 'status' => 'error'], 400);

        // menambahkan middleware JwtBlacklistMiddleware menambahkan di kernel di php
    }
}
