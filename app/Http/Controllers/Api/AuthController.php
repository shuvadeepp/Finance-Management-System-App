<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['required', 'in:ADMIN,MANAGER,EMPLOYEE'],
        ]);

        $user = User::create([
            'username'      => $validated['username'],
            'password_hash' => Hash::make($validated['password']),
            'role'          => $validated['role'],
            'is_active'     => 1,
        ]);

        return response()->json([
            'message' => 'User Registered',
            'user'    => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $validated['username'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid Username'
            ], 401);
        }

        if (!Hash::check($validated['password'], $user->password_hash)) {
            return response()->json([
                'message' => 'Invalid Password'
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'message' => 'Account is inactive'
            ], 403);
        }

        $token = JWTAuth::claims([
            'user_id'  => $user->id,
            'username' => $user->username,
            'role'     => $user->role,
        ])->fromUser($user);

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }
 
 
    public function resetPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'username' => 'required',
                'otp' => 'required',
                'password' => 'required|min:8'
            ],
            [
                'username.required' => 'Username is required',
                'otp.required' => 'OTP is required',
                'password.required' => 'Password is required',
                'password.min' => 'Password must be at least 8 characters'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $reset = DB::table('password_resets')
                    ->where('username', $request->username)
                    ->first();

        if (!$reset) {
            return response()->json([
                'success' => false,
                'message' => 'Reset request not found.',
            ], 404);
        }

        // Check OTP
        if ($request->otp != $reset->otp) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP.',
            ], 400);
        }

        // Check Expiry
        if (Carbon::parse($reset->expires_at)->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired.',
            ], 400);
        }

        // Find User
        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Update Password
        $user->update([
            'password_hash' => Hash::make($request->password),
        ]);

        // Delete Used OTP
        DB::table('password_resets')
            ->where('username', $request->username)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'username' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Username not found'
            ], 404);
        }

        $otp = rand(1000, 9999);

        PasswordReset::where('username', $request->username)->delete();

        PasswordReset::create([
            'username' => $request->username,
            'otp' => $otp,
            'expires_at' => now()->addMinutes(12)
        ]);

        return response()->json([
            'message' => 'OTP generated successfully',
            'otp' => $otp 
        ]);
    }

    public function verifyOtp(Request $request) {

        $request->validate([
            'username' => 'required',
            'otp' => 'required'
        ]);

        $record = PasswordReset::where(
            'username',
            $request->username
        )
        ->where(
            'otp',
            $request->otp
        )
        ->first();

        $secret_key = '%^&*(*&^%$%^&*&^%$%^&*(*&^%$%^&*' . $request->otp . '%^&*)*&^%$%^&*&^%$%^&*)*&^%$%^&*';
        // dd($request->otp);

        if(!$record)
        {
            return response()->json([
                'message' => 'Invalid OTP'
            ],422);
        }

        if(now()->gt($record->expires_at))
        {
            return response()->json([
                'message' => 'OTP expired'
            ],422);
        }

        return response()->json([
            'message' => 'OTP verified',
            'secret_key' => $secret_key
        ]);
    }


    
}