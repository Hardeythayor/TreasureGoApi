<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TreasureHunt;
use App\Models\User;
use App\Models\UserTierSubscription;
use App\Notifications\PasswordResetCodeNotification;
use App\Notifications\VerifyEmailCode;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'max:255', 'alpha_dash',
                Rule::unique('users', 'username')->whereNull('deleted_at'),
            ],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->whereNull('deleted_at'),
            ],
            'country' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'country' => $request->country,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');

        $this->sendVerificationCode($user);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful. Please verify your email with the code we sent you.',
            'user' => $this->formatUser($user),
            'token' => $token,
        ], 201);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $data = $this->formatUser($user);

        if ($user->hasRole('user')) {
            $data['current_subscription'] = UserTierSubscription::where('user_id', $user->id)
                ->where('is_current', 'yes')
                ->where('status', 'active')
                ->with('subscriptionTier')
                ->first();

            $data['total_treasures_found'] = TreasureHunt::where('user_id', $user->id)
                ->where('status', 'found')
                ->count();
        }

        return response()->json([
            'user' => $data,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => [
                'sometimes', 'string', 'max:255', 'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'email' => [
                'sometimes', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id)->whereNull('deleted_at'),
            ],
            'country' => ['sometimes', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $emailChanged = $request->filled('email') && $request->email !== $user->email;

        $user->update($validator->validated());

        if ($emailChanged) {
            $user->forceFill(['email_verified_at' => null])->save();
            $this->sendVerificationCode($user);
        }

        return response()->json([
            'message' => $emailChanged
                ? 'Profile updated. Please verify your new email with the code we sent you.'
                : 'Profile updated successfully.',
            'user' => $this->formatUser($user),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Your current password is incorrect.'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $user->forceFill([
                'password_reset_code' => $code,
                'password_reset_code_expires_at' => now()->addMinutes(10),
            ])->save();

            $user->notify(new PasswordResetCodeNotification($code));
        }

        return response()->json(['message' => 'If that email address is registered, a password reset code has been sent to it.']);
    }

    public function verifyResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (
            ! $user
            || $user->password_reset_code !== $request->code
            || $user->password_reset_code_expires_at === null
            || $user->password_reset_code_expires_at->isPast()
        ) {
            return response()->json(['message' => 'The password reset code is invalid or has expired.'], 422);
        }

        // Exchange the one-time code for a longer, unguessable reset token so
        // resetPassword() doesn't need to re-check the 6-digit code itself.
        $resetToken = Str::random(60);

        $user->forceFill([
            'password_reset_code' => $resetToken,
            'password_reset_code_expires_at' => now()->addMinutes(10),
        ])->save();

        return response()->json([
            'message' => 'Code verified.',
            'reset_token' => $resetToken,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'reset_token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (
            ! $user
            || $user->password_reset_code !== $request->reset_token
            || $user->password_reset_code_expires_at === null
            || $user->password_reset_code_expires_at->isPast()
        ) {
            return response()->json(['message' => 'This password reset session is invalid or has expired.'], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        $user->forceFill([
            'password_reset_code' => null,
            'password_reset_code_expires_at' => null,
        ])->save();

        $user->tokens()->delete();

        return response()->json(['message' => 'Password reset successfully. Please log in with your new password.']);
    }

    private function formatUser(User $user): array
    {
        return array_merge($user->toArray(), [
            'roles' => $user->getRoleNames(),
        ]);
    }

    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified.']);
        }

        if (
            $user->verification_code !== $request->code
            || $user->verification_code_expires_at === null
            || $user->verification_code_expires_at->isPast()
        ) {
            return response()->json(['message' => 'The verification code is invalid or has expired.'], 422);
        }

        $user->forceFill([
            'verification_code' => null,
            'verification_code_expires_at' => null,
        ])->save();

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Email verified successfully.']);
    }

    public function resendVerificationCode(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email is already verified.']);
        }

        $this->sendVerificationCode($user);

        return response()->json(['message' => 'A new verification code has been sent to your email.']);
    }

    private function sendVerificationCode(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'verification_code' => $code,
            'verification_code_expires_at' => now()->addMinutes(10),
        ])->save();

        $user->notify(new VerifyEmailCode($code));
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Your account has been deactivated. Please contact support.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $this->formatUser($user),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
