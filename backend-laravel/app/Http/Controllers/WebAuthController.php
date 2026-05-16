<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class WebAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            $user = Auth::user();
            if ($user->role === 'admin') return redirect()->intended('/admin/dashboard');
            if ($user->role === 'seller') return redirect()->intended('/seller/dashboard');
            
            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255|unique:users',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->username, // Default name to username
            'username' => $request->username,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'role' => 'customer',
            'status' => 'active',
            'isVerified' => true,
        ]);

        Auth::login($user);

        return redirect('/');
    }

    public function showSellerRegister()
    {
        return view('auth.seller-register');
    }

    public function sellerRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'mobileNumber' => 'required|string|max:20',
            'gcashNumber' => 'nullable|string|max:20',
            'indigencyCertificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'validId' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'gcashQrCode' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'shopName' => 'nullable|string|max:255',
            'shopAddress' => 'nullable|string|max:1000',
            'businessPermit' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = [
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'mobileNumber' => $request->mobileNumber,
            'gcashNumber' => $request->gcashNumber,
            'shopName' => $request->shopName ?? $request->name . "'s Workshop",
            'shopAddress' => $request->shopAddress ?? 'Not Provided',
            'role' => 'seller',
            'status' => 'active',
            'isVerified' => false, // Needs admin approval
        ];

        // Handle file uploads
        if ($request->hasFile('indigencyCertificate')) {
            $path = $request->file('indigencyCertificate')->move(public_path('uploads/requirements'), time().'_residency.'.$request->file('indigencyCertificate')->getClientOriginalExtension());
            $data['indigencyCertificate'] = '/uploads/requirements/'.basename($path);
        }

        if ($request->hasFile('validId')) {
            $path = $request->file('validId')->move(public_path('uploads/requirements'), time().'_id.'.$request->file('validId')->getClientOriginalExtension());
            $data['validId'] = '/uploads/requirements/'.basename($path);
        }

        if ($request->hasFile('gcashQrCode')) {
            $path = $request->file('gcashQrCode')->move(public_path('uploads/requirements'), time().'_gcashqr.'.$request->file('gcashQrCode')->getClientOriginalExtension());
            $data['gcashQrCode'] = '/uploads/requirements/'.basename($path);
        }

        if ($request->hasFile('businessPermit')) {
            $path = $request->file('businessPermit')->move(public_path('uploads/requirements'), time().'_permit.'.$request->file('businessPermit')->getClientOriginalExtension());
            $data['businessPermit'] = '/uploads/requirements/'.basename($path);
        }

        $user = User::create($data);

        return redirect()->route('login')->with('success', 'Your artisan application has been submitted and is awaiting approval.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }

    public function handleGoogleLogin(Request $request)
    {
        try {
            $credential = $request->credential;
            $client = new \Google\Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($credential);

            if (!$payload) {
                return back()->withErrors(['email' => 'Google authentication failed.']);
            }

            $email = strtolower($payload['email']);
            $googleId = $payload['sub'];
            $name = $payload['name'];
            $picture = $payload['picture'] ?? null;

            $user = User::where('googleId', $googleId)
                ->orWhere('email', $email)
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(\Illuminate\Support\Str::random(32)),
                    'role' => 'customer',
                    'status' => 'active',
                    'isVerified' => true,
                    'googleId' => $googleId,
                    'profilePhoto' => $picture,
                    'hasPasswordSet' => false
                ]);
            } else {
                if (!$user->googleId) {
                    $user->googleId = $googleId;
                    $user->save();
                }
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->intended('/');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'An error occurred during Google authentication.']);
        }
    }

    public function profile()
    {
        $user = Auth::user();
        return view('profile.index', compact('user'));
    }

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', strtolower($request->email))->first();
        // Use Laravel's built-in password reset
        \Illuminate\Support\Facades\Password::sendResetLink($request->only('email'));
        return back()->with('status', 'If that email exists, a password reset link has been sent.');
    }

    public function updateProfile(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $request->validate([
            'name'         => 'required|string|max:255',
            'mobileNumber' => 'nullable|string|max:20',
        ]);

        $user->name         = $request->name;
        $user->mobileNumber = $request->mobileNumber;
        $user->save();

        return redirect()->route('profile')->with('success', 'Profile updated successfully!');
    }
}
