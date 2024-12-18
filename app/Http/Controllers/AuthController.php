<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|string|unique:users,username|min:5|max:15',
            'email' => 'required|unique:users,email|',
            'password' => [
                'required',
                'min:8',
                'regex:/^(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]+$/',
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

        try {
            $user = User::create([
                'name' => $request->input('name'),
                'username' => $request->input('username'),
                'email' => $request->input('email'),
                'password' => bcrypt($request->input('password')),
            ]);

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
                'message' => 'Registration failed',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email_or_username' => 'required',
            'password' => 'required',
        ]);

        try {
            $loginField = filter_var($request->email_or_username, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

            // Prepare credentials
            $credentials = [
                $loginField => $request->email_or_username,
                'password' => $request->password
            ];

            $user = User::where($loginField, $request->email_or_username)->first();

            $token = $user->createToken('token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'code' => 200,
                'message' => 'Login successful',
                'token' => $token
            ]);

            // ini masih percobaan

            // try {
            //     if (!$token = JWTAuth::attempt($credentials)) {
            //         return response()->json([
            //             'status' => 'failed',
            //             'code' => 401,
            //             'message' => 'Unauthorized',
            //         ], 401);
            //     }

            //     $user = User::where($loginField, $request->email_or_username)->first();

            //     $customClaims = [
            //         'id' => $user->id,
            //         'name' => $user->name,
            //         'username' => $user->username,
            //         'email' => $user->email,
            //     ];

            //     $token = JWTAuth::claims($customClaims)->attempt($credentials);

            //     return response()->json([
            //         'status' => 'success',
            //         'code' => 200,
            //         'message' => 'Login successful',
            //         'token' => $user
            //     ]);

            // } catch (\Throwable $th) {
            //     return response()->json([
            //         'status' => 'failed',
            //         'code' => 500,
            //         'message' => 'Error Creating Token',
            //     ], 500);
            // }

        } catch (\Throwable $err) {
            return response()->json([
                'status' => 'failed',
                'code' => 500,
                'message' => 'Internal Server Error',
            ], 500);
        }      
    }

    public function logout(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Please Login First'], 401);
        }

        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'code' => 200,
            'message' => 'Logout Success'
        ], 200);
    }
}
