<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        try {
            // Attempt to send the reset link
            $status = Password::sendResetLink(
                $request->only('email')
            );

            // Handle different status codes
            switch ($status) {
                case Password::RESET_LINK_SENT:
                    return response()->json(['message' => 'Reset link sent.']);
                case Password::INVALID_USER:
                    return response()->json(['message' => 'We can\'t find a user with that email address.'], 404);
                case Password::RESET_THROTTLED:
                    return response()->json(['message' => 'You have requested a password reset too many times. Please try again later.'], 429);
                default:
                    // Log the status if it's something else (e.g., generic error with sending)
                    Log::error("Password reset failed for email {$request->email}: {$status}");
                    return response()->json(['message' => 'Unable to send reset link.'], 500);
            }
        } catch (\Exception $e) {
            // Log any exception that occurs
            Log::error("Error sending password reset link for email {$request->email}: " . $e->getMessage());

            // Return a more detailed error message
            return response()->json([
                'message' => 'An error occurred while sending the reset link.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password reset successful.'])
            : response()->json(['message' => 'Invalid token or email.'], 400);
    }
}
