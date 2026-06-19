<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite; // @phpstan-ignore-line

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     tags={"Auth"},
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password","role"},
     *             @OA\Property(property="username", type="string", example="john_doe"),
     *             @OA\Property(property="password", type="string", example="secret123"),
     *             @OA\Property(property="role", type="string", enum={"ADMIN","MANAGER","EMPLOYEE"})
     *         )
     *     ),
     *     @OA\Response(response=201, description="User Registered"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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

    /**
     * @OA\Post(
     *     path="/login",
     *     tags={"Auth"},
     *     summary="Login and receive a JWT token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","password"},
     *             @OA\Property(property="username", type="string", example="john_doe"),
     *             @OA\Property(property="password", type="string", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Invalid credentials"),
     *     @OA\Response(response=403, description="Account inactive")
     * )
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('username', $validated['username'])->first();

        if (!$user) {
            AuditLog::create([
                'user_id'     => null,
                'action'      => 'LOGIN_FAILED',
                'description' => 'Failed login attempt for username: ' . $validated['username'],
                'ip_address'  => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid Username'], 401);
        }

        if (!Hash::check($validated['password'], $user->password_hash)) {
            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'LOGIN_FAILED',
                'description' => 'Wrong password for username: ' . $validated['username'],
                'ip_address'  => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid Password'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account is inactive'], 403);
        }

        $token = JWTAuth::claims([
            'user_id'  => $user->id,
            'username' => $user->username,
            'role'     => $user->role,
        ])->fromUser($user);

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'LOGIN',
            'description' => 'User logged in successfully',
            'ip_address'  => $request->ip(),
        ]);

        return response()->json([
            'token' => $token,
            'user'  => $user,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     tags={"Auth"},
     *     summary="Logout and invalidate JWT token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logged out successfully"),
     *     @OA\Response(response=500, description="Failed to logout")
     * )
     */
    public function logout(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'LOGOUT',
                'description' => 'User logged out',
                'ip_address'  => $request->ip(),
            ]);

            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to logout'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/refresh-token",
     *     tags={"Auth"},
     *     summary="Refresh JWT token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="New token",
     *         @OA\JsonContent(@OA\Property(property="token", type="string"))
     *     ),
     *     @OA\Response(response=401, description="Token expired or invalid")
     * )
     */
    public function refreshToken()
    {
        try {
            $newToken = JWTAuth::parseToken()->refresh();

            return response()->json(['token' => $newToken]);
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'Token expired, please login again'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token invalid'], 401);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Could not refresh token'], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/forgot-password",
     *     tags={"Auth"},
     *     summary="Request a password reset OTP",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username"},
     *             @OA\Property(property="username", type="string", example="john_doe")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP generated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="otp", type="integer", example=1234)
     *         )
     *     ),
     *     @OA\Response(response=404, description="Username not found")
     * )
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['username' => 'required']);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['message' => 'Username not found'], 404);
        }

        $otp = rand(1000, 9999);

        PasswordReset::where('username', $request->username)->delete();

        PasswordReset::create([
            'username'   => $request->username,
            'otp'        => $otp,
            'expires_at' => now()->addMinutes(12),
        ]);

        return response()->json([
            'message' => 'OTP generated successfully',
            'otp'     => $otp,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/verify-otp",
     *     tags={"Auth"},
     *     summary="Verify OTP for password reset",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","otp"},
     *             @OA\Property(property="username", type="string", example="john_doe"),
     *             @OA\Property(property="otp", type="integer", example=1234)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="secret_key", type="string")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Invalid or expired OTP")
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'otp'      => 'required',
        ]);

        $record = PasswordReset::where('username', $request->username)
            ->where('otp', $request->otp)
            ->first();

        $secret_key = '%^&*(*&^%$%^&*&^%$%^&*(*&^%$%^&*' . $request->otp . '%^&*)*&^%$%^&*&^%$%^&*)*&^%$%^&*';

        if (!$record) {
            return response()->json(['message' => 'Invalid OTP'], 422);
        }

        if (now()->gt($record->expires_at)) {
            return response()->json(['message' => 'OTP expired'], 422);
        }

        return response()->json([
            'message'    => 'OTP verified',
            'secret_key' => $secret_key,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/reset-password",
     *     tags={"Auth"},
     *     summary="Reset password using OTP",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"username","otp","password"},
     *             @OA\Property(property="username", type="string", example="john_doe"),
     *             @OA\Property(property="otp", type="integer", example=1234),
     *             @OA\Property(property="password", type="string", minLength=8, example="newpassword")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successfully"),
     *     @OA\Response(response=400, description="Invalid or expired OTP"),
     *     @OA\Response(response=404, description="Reset request or user not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'username' => 'required',
                'otp'      => 'required',
                'password' => 'required|min:8',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $reset = DB::table('password_resets')
            ->where('username', $request->username)
            ->first();

        if (!$reset) {
            return response()->json(['success' => false, 'message' => 'Reset request not found.'], 404);
        }

        if ($request->otp != $reset->otp) {
            return response()->json(['success' => false, 'message' => 'Invalid OTP.'], 400);
        }

        if (Carbon::parse($reset->expires_at)->isPast()) {
            return response()->json(['success' => false, 'message' => 'OTP has expired.'], 400);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }

        $user->update(['password_hash' => Hash::make($request->password)]);

        DB::table('password_resets')->where('username', $request->username)->delete();

        return response()->json(['success' => true, 'message' => 'Password reset successfully.']);
    }

    /**
     * @OA\Get(
     *     path="/auth/github/redirect",
     *     tags={"Auth"},
     *     summary="Get GitHub OAuth redirect URL",
     *     @OA\Response(
     *         response=200,
     *         description="GitHub redirect URL",
     *         @OA\JsonContent(@OA\Property(property="url", type="string"))
     *     )
     * )
     */
    public function githubRedirect()
    {
        $url = Socialite::driver('github')->stateless()
            ->with(['prompt' => 'login'])
            ->redirect()->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * @OA\Get(
     *     path="/auth/github/callback",
     *     tags={"Auth"},
     *     summary="GitHub OAuth callback (handled by GitHub, not called directly)",
     *     @OA\Response(response=302, description="Redirect to frontend with token")
     * )
     */
    public function githubCallback()
    {
        try {
            $githubUser = Socialite::driver('github')->stateless()->user();

            $githubId = $githubUser->getId();
            $email    = $githubUser->getEmail();
            $name     = $githubUser->getNickname() ?? $githubUser->getName();
            $avatar   = $githubUser->getAvatar();

            $user = User::where('github_id', $githubId)->first();

            if (!$user && $email) {
                $user = User::where('email', $email)->first();
            }

            if (!$user) {
                $user = User::create([
                    'username'  => $name,
                    'role'      => 'GIT USER',
                    'is_active' => 1,
                    'github_id' => $githubId,
                    'email'     => $email,
                    'avatar'    => $avatar,
                ]);
            } else {
                $user->update([
                    'github_id' => $githubId,
                    'avatar'    => $avatar,
                ]);
            }

            $token = JWTAuth::claims([
                'user_id'  => $user->id,
                'username' => $user->username,
                'role'     => $user->role,
            ])->fromUser($user);

            AuditLog::create([
                'user_id'     => $user->id,
                'action'      => 'LOGIN',
                'description' => 'User logged in via GitHub OAuth',
                'ip_address'  => request()->ip(),
            ]);

            $query = http_build_query([
                'token'    => $token,
                'username' => $user->username,
                'role'     => $user->role,
                'user_id'  => $user->id,
                'avatar'   => $user->avatar,
            ]);

            return redirect('http://localhost:4200/auth/github/success?' . $query);
        } catch (\Exception) {
            return redirect('http://localhost:4200/login?error=github_failed');
        }
    }
}
