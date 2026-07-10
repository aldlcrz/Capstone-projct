<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Utils\SocketUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private function trimTrailingSlash(mixed $value)
    {
        return rtrim($value, '/');
    }

    private function generateOTP()
    {
        return (string) rand(100000, 990000);
    }

    public function register(Request $request)
    {
        try {
            $name = $request->input('name');
            $email = strtolower(trim($request->input('email')));
            $password = $request->input('password');
            $role = $request->input('role', 'customer');
            $mobileNumber = $request->input('mobileNumber');
            $gcashNumber = $request->input('gcashNumber');
            $isAdult = $request->input('isAdult');

            // Email validation
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['message' => 'Please provide a valid email address'], 400);
            }

            if (strlen($email) > 255) {
                return response()->json(['message' => 'Email cannot exceed 255 characters'], 400);
            }

            if (strlen($password) < 6 || strlen($password) > 30) {
                return response()->json(['message' => 'Password must be between 6 and 30 characters long'], 400);
            }

            // Role-specific Validations
            $sellerMobile = null;
            $sellerGcash = null;
            if ($role === 'seller') {
                if (!$mobileNumber || !$gcashNumber) {
                    return response()->json(['message' => 'Sellers must provide both a mobile number and a GCash number'], 400);
                }
                $sellerMobile = $mobileNumber;
                $sellerGcash = $gcashNumber;
            }

            $existingUser = User::where('email', $email)->first();
            if ($existingUser) {
                return response()->json(['message' => 'An account with this email already exists'], 400);
            }

            // Handle uploads
            $indigencyCertificate = null;
            $validId = null;
            $gcashQrCode = null;

            if ($request->hasFile('indigencyCertificate')) {
                $file = $request->file('indigencyCertificate');
                $indigencyCertificate = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/products'), $indigencyCertificate);
            }
            if ($request->hasFile('validId')) {
                $file = $request->file('validId');
                $validId = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/products'), $validId);
            }
            if ($request->hasFile('gcashQrCode')) {
                $file = $request->file('gcashQrCode');
                $gcashQrCode = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/products'), $gcashQrCode);
            }

            $newUser = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'role' => $role,
                'indigencyCertificate' => $indigencyCertificate,
                'validId' => $validId,
                'gcashQrCode' => $gcashQrCode,
                'mobileNumber' => $sellerMobile,
                'gcashNumber' => $sellerGcash,
                'isAdult' => $isAdult === 'true' || $isAdult === true,
            ]);

            // Realtime socket update
            SocketUtility::emitUserUpdated($newUser, ['action' => 'registered']);

            // Notify admins
            if ($newUser->role === 'seller') {
                $admins = User::where('role', 'admin')->get();
                foreach ($admins as $admin) {
                    \App\Models\Notification::create([
                        'userId' => $admin->id,
                        'title' => 'New Seller Registration',
                        'message' => "Seller {$newUser->name} registered and is pending approval.",
                        'type' => 'system',
                        'targetRole' => 'admin',
                    ]);
                    SocketUtility::emitToUser($admin->id, 'new_notification', [
                        'title' => 'New Seller Registration',
                        'message' => "Seller {$newUser->name} registered and is pending approval.",
                    ]);
                }
            }

            $token = JWTAuth::fromUser($newUser);

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $newUser->id,
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'role' => $newUser->role,
                    'isVerified' => $newUser->isVerified,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $email = strtolower(trim($request->input('email')));
            $password = $request->input('password');

            $user = User::where('email', $email)->first();
            if (!$user) {
                return response()->json(['message' => 'Invalid email or password'], 400);
            }

            // Lockout checks
            if ($user->loginLockedUntil && $user->loginLockedUntil->isFuture()) {
                $diff = $user->loginLockedUntil->diffInSeconds(now());
                return response()->json(['message' => "Account locked. Please try again after {$diff} seconds."], 400);
            }

            if (!Hash::check($password, $user->password)) {
                $user->increment('loginAttempts');
                if ($user->loginAttempts >= 5) {
                    $user->update([
                        'loginLockedUntil' => now()->addMinutes(15),
                        'loginAttempts' => 0
                    ]);
                    return response()->json(['message' => 'Too many login attempts. Account locked for 15 minutes.'], 400);
                }
                return response()->json(['message' => 'Invalid email or password'], 400);
            }

            $user->update(['loginAttempts' => 0, 'loginLockedUntil' => null]);

            if ($user->status !== 'active') {
                $status = $user->status;
                $reason = $status === 'rejected' ? $user->rejectionReason : $user->violationReason;
                $msg = $status === 'blocked' ? 'Account Terminated' : ($status === 'rejected' ? 'Registration Rejected' : 'Violation Detected');
                return response()->json([
                    'message' => $msg,
                    'status' => $status,
                    'reason' => $reason ?: ($status === 'blocked' ? 'Account terminated by administrator.' : 'Access restricted.')
                ], 401);
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'isVerified' => $user->isVerified,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function forgotPassword(Request $request)
    {
        try {
            $email = strtolower(trim($request->input('email')));
            $user = User::where('email', $email)->first();
            if (!$user) {
                // Return generic message for privacy
                return response()->json(['message' => 'If an account with that email exists, an OTP has been sent.'], 200);
            }

            $otp = $this->generateOTP();
            $expires = now()->addMinutes(10);

            $user->update([
                'resetPasswordToken' => $otp,
                'resetPasswordExpires' => $expires
            ]);

            // Send Email (SMTP / Mail log)
            try {
                Mail::raw("Your password reset OTP is: {$otp}. It will expire in 10 minutes.", function ($message) use ($email) {
                    $message->to($email)->subject('Password Reset OTP');
                });
            } catch (\Exception $mailEx) {
                // Log mail exception, keep running
                logger()->error('Forgot password email failed to send: ' . $mailEx->getMessage());
            }

            return response()->json(['message' => 'Password reset OTP sent to email.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function verifyOtp(Request $request)
    {
        try {
            $email = strtolower(trim($request->input('email')));
            $otp = trim($request->input('otp'));

            $user = User::where('email', $email)
                ->where('resetPasswordToken', $otp)
                ->where('resetPasswordExpires', '>', now())
                ->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid or expired OTP'], 400);
            }

            return response()->json(['message' => 'OTP verified successfully. Proceed to reset password.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $email = strtolower(trim($request->input('email')));
            $otp = trim($request->input('otp'));
            $newPassword = $request->input('newPassword');

            if (strlen($newPassword) < 6 || strlen($newPassword) > 30) {
                return response()->json(['message' => 'Password must be between 6 and 30 characters long'], 400);
            }

            $user = User::where('email', $email)
                ->where('resetPasswordToken', $otp)
                ->where('resetPasswordExpires', '>', now())
                ->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid or expired OTP verification link'], 400);
            }

            $user->update([
                'password' => Hash::make($newPassword),
                'resetPasswordToken' => null,
                'resetPasswordExpires' => null,
                'passwordChangedAt' => now(),
            ]);

            return response()->json(['message' => 'Password has been reset successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function googleLogin(Request $request)
    {
        try {
            $idToken = $request->input('idToken');
            if (!$idToken) {
                return response()->json(['message' => 'ID Token is required'], 400);
            }

            // Google OAuth2 Verification
            $response = Http::get("https://oauth2.googleapis.com/tokeninfo", ['id_token' => $idToken]);
            if ($response->failed()) {
                return response()->json(['message' => 'Invalid Google token'], 400);
            }

            $payload = $response->json();
            $email = strtolower(trim($payload['email'] ?? ''));
            $name = $payload['name'] ?? 'Google User';
            $googleId = $payload['sub'] ?? '';

            if (!$email) {
                return response()->json(['message' => 'Email not provided by Google account'], 400);
            }

            // Find or create user
            $user = User::where('googleId', $googleId)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'googleId' => $googleId,
                    'hasPasswordSet' => false,
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'customer',
                    'isVerified' => true,
                ]);
            } else {
                if (!$user->googleId) {
                    $user->update(['googleId' => $googleId]);
                }
            }

            if ($user->status !== 'active') {
                return response()->json(['message' => 'Access denied: Restricted account'], 401);
            }

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'isVerified' => $user->isVerified,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function setPassword(Request $request)
    {
        try {
            $user = User::find(Auth::id());
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $password = $request->input('password');
            if (strlen($password) < 6) {
                return response()->json(['message' => 'Password must be at least 6 characters long'], 400);
            }

            $user->update([
                'password' => Hash::make($password),
                'hasPasswordSet' => true,
                'passwordChangedAt' => now()
            ]);

            return response()->json(['message' => 'Password set successfully'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Server error', 'error' => $e->getMessage()], 500);
        }
    }

    public function getProfile()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        return response()->json($user);
    }
}
