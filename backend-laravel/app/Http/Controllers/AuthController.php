<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Handle user login.
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        }

        $user = User::where('email', strtolower($request->email))->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Account status checks
        if ($user->status === 'blocked') {
            return response()->json([
                'message' => 'Account Terminated',
                'reason' => $user->violationReason ?? 'Account terminated by administrator.',
                'status' => 'blocked'
            ], 403);
        }

        if ($user->status === 'frozen') {
            return response()->json([
                'message' => 'Account Frozen',
                'reason' => $user->violationReason ?? 'Account suspended for policy violations.',
                'status' => 'frozen'
            ], 403);
        }

        if ($user->status === 'rejected') {
            return response()->json([
                'message' => 'Registration Rejected',
                'reason' => $user->rejectionReason ?? 'Application does not meet community standards.',
                'status' => 'rejected'
            ], 403);
        }

        // Seller pending approval gate
        if ($user->role === 'seller' && !$user->isVerified) {
            return response()->json([
                'message' => 'Account Pending Approval',
                'reason' => 'Your seller account is awaiting admin approval. You will be notified once approved.',
                'status' => 'pending'
            ], 403);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 400);
        }

        // Reset login attempts (if we implement them)
        // $user->loginAttempts = 0;
        // $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'isVerified' => $user->isVerified,
            ],
        ]);
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'nullable|string|in:customer,seller,admin',
            'mobileNumber' => 'required_if:role,seller|string',
            'gcashNumber' => 'required_if:role,seller|string',
            'isAdult' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 400);
        }

        $data = [
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'customer',
            'isVerified' => $request->role === 'seller' ? false : true,
            'status' => 'active',
            'isAdult' => $request->isAdult === 'true' || $request->isAdult === true,
        ];

        if ($request->role === 'seller') {
            $data['mobileNumber'] = $request->mobileNumber;
            $data['gcashNumber'] = $request->gcashNumber;

            // Handle file uploads
            if ($request->hasFile('indigencyCertificate')) {
                $file = $request->file('indigencyCertificate');
                $filename = time() . '_indigency_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/verification'), $filename);
                $data['indigencyCertificate'] = $filename;
            }
            if ($request->hasFile('validId')) {
                $file = $request->file('validId');
                $filename = time() . '_id_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/verification'), $filename);
                $data['validId'] = $filename;
            }
            if ($request->hasFile('gcashQrCode')) {
                $file = $request->file('gcashQrCode');
                $filename = time() . '_gcashqr_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/verification'), $filename);
                $data['gcashQrCode'] = $filename;
            }
        }

        $user = User::create($data);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 201);
    }

    /**
     * Handle Google Login.
     */
    public function googleLogin(Request $request)
    {
        try {
            $credential = $request->credential;
            if (!$credential) {
                return response()->json(['message' => 'Google credential is required'], 400);
            }

            $client = new GoogleClient(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($credential);

            if (!$payload) {
                return response()->json(['message' => 'Google authentication failed'], 401);
            }

            $email = strtolower($payload['email']);
            $googleId = $payload['sub'];
            $name = $payload['name'] ?? explode('@', $email)[0];
            $picture = $payload['picture'] ?? null;

            $user = User::where('googleId', $googleId)
                ->orWhere('email', $email)
                ->first();

            $isNewUser = false;

            if (!$user) {
                $isNewUser = true;
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'customer',
                    'status' => 'active',
                    'isVerified' => true,
                    'googleId' => $googleId,
                    'profilePhoto' => $picture,
                    'hasPasswordSet' => false
                ]);
            } else {
                // Account status checks
                if ($user->status === 'blocked' || $user->status === 'frozen') {
                    return response()->json([
                        'message' => 'Account Restricted',
                        'reason' => $user->violationReason ?? 'Account restricted by administrator.',
                        'status' => $user->status
                    ], 403);
                }

                // Link googleId if not already linked
                if (!$user->googleId) {
                    $user->googleId = $googleId;
                    if ($picture && !$user->profilePhoto) {
                        $user->profilePhoto = $picture;
                    }
                    $user->save();
                }
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'isNewUser' => $isNewUser,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'isVerified' => $user->isVerified,
                    'profilePhoto' => $user->profilePhoto,
                    'hasPasswordSet' => (bool)$user->hasPasswordSet
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Google Login Error: ' . $e->getMessage());
            return response()->json(['message' => 'Google authentication failed'], 500);
        }
    }

    /**
     * Handle Forgot Password (Send OTP).
     */
    public function forgotPassword(Request $request)
    {
        $email = strtolower($request->email);
        if (!$email) return response()->json(['message' => 'Email is required'], 400);

        $user = User::where('email', $email)->first();
        if (!$user) {
            // Standard security: don't reveal if user exists
            return response()->json(['message' => 'If an account exists, a 6-digit code has been sent.']);
        }

        $otp = (string) rand(100000, 999999);
        $user->resetPasswordToken = hash('sha256', $otp);
        $user->resetPasswordExpires = Carbon::now()->addMinutes(60);
        $user->save();

        // In a real app, send the email here. For now, we'll log it or return it in dev.
        Log::info("Password Reset OTP for {$email}: {$otp}");

        $response = ['message' => 'A 6-digit code has been sent to your email.'];
        if (config('app.debug')) {
            $response['devOtp'] = $otp; // Helping the user during development
        }

        return response()->json($response);
    }

    /**
     * Verify OTP.
     */
    public function verifyOtp(Request $request)
    {
        $email = strtolower($request->email);
        $otp = $request->otp;

        if (!$email || !$otp) {
            return response()->json(['message' => 'Email and OTP are required'], 400);
        }

        $user = User::where('email', $email)
            ->where('resetPasswordToken', hash('sha256', $otp))
            ->where('resetPasswordExpires', '>', Carbon::now())
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }

        return response()->json([
            'message' => 'OTP verified successfully',
            'resetToken' => $otp
        ]);
    }

    /**
     * Reset Password.
     */
    public function resetPassword(Request $request)
    {
        $token = $request->token; // This is the OTP
        $password = $request->password;

        if (!$token || !$password) {
            return response()->json(['message' => 'Reset token and new password are required'], 400);
        }

        if (strlen($password) < 6) {
            return response()->json(['message' => 'Password must be at least 6 characters long'], 400);
        }

        $user = User::where('resetPasswordToken', hash('sha256', $token))
            ->where('resetPasswordExpires', '>', Carbon::now())
            ->first();

        if (!$user) {
            return response()->json(['message' => 'Reset link is invalid or has expired'], 400);
        }

        $user->password = Hash::make($password);
        $user->resetPasswordToken = null;
        $user->resetPasswordExpires = null;
        $user->save();

        return response()->json(['message' => 'Password reset successful']);
    }

    /**
     * Get the authenticated user's profile.
     */
    public function getProfile(Request $request)
    {
        return response()->json($request->user());
    }
}
