<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CommissionRecord;
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
            /** @var User $user */
            $user = Auth::user();

            if ($user->status === 'frozen') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Pay commission to continue',
                ])->onlyInput('email');
            }

            if ($user->status === 'blocked') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been blocked. Reason: ' . ($user->violationReason ?? 'Policy violation'),
                ])->onlyInput('email');
            }

            if ($user->status === 'rejected') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your application has been rejected. Reason: ' . ($user->rejectionReason ?? 'Did not meet requirements'),
                ])->onlyInput('email');
            }

            $request->session()->regenerate();

            // Restore cart: merge saved DB cart with any current guest session cart
            $guestCart    = session()->get('cart', []);
            $savedCartRaw = $user->cart;
            $savedCart    = $savedCartRaw ? (json_decode($savedCartRaw, true) ?? []) : [];

            // Guest cart items take precedence (higher qty) over saved items
            $mergedCart = $savedCart;
            foreach ($guestCart as $key => $item) {
                if (isset($mergedCart[$key])) {
                    $mergedCart[$key]['quantity'] = max(
                        (int) ($mergedCart[$key]['quantity'] ?? 1),
                        (int) ($item['quantity'] ?? 1)
                    );
                } else {
                    $mergedCart[$key] = $item;
                }
            }
            session()->put('cart', $mergedCart);
            // Persist merged cart back to DB
            if ($user instanceof User) {
                $user->update(['cart' => json_encode($mergedCart)]);
            }

            if ($user->role === 'superadmin') return redirect()->route('superadmin.dashboard');
            if ($user->role === 'admin') return redirect()->route('admin.dashboard');
            if ($user->role === 'seller') return redirect()->route('seller.dashboard');

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
            'residencyCertificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'birDocument' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'shopName' => 'nullable|string|max:255',
            'shopAddress' => 'nullable|string|max:1000',
            'businessPermit' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
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
        if ($request->hasFile('residencyCertificate')) {
            $path = $request->file('residencyCertificate')->move(public_path('uploads/requirements'), time().'_residency.'.$request->file('residencyCertificate')->getClientOriginalExtension());
            $data['residencyCertificate'] = '/uploads/requirements/'.basename($path);
        }

        if ($request->hasFile('birDocument')) {
            $path = $request->file('birDocument')->move(public_path('uploads/requirements'), time().'_bir.'.$request->file('birDocument')->getClientOriginalExtension());
            $data['birDocument'] = '/uploads/requirements/'.basename($path);
        }

        if ($request->hasFile('businessPermit')) {
            $path = $request->file('businessPermit')->move(public_path('uploads/requirements'), time().'_permit.'.$request->file('businessPermit')->getClientOriginalExtension());
            $data['businessPermit'] = '/uploads/requirements/'.basename($path);
        }

        $user = User::create($data);

        // Notify admins about the new seller registration
        \App\Models\Notification::sendToAdmins(
            'New Seller Application',
            "Artisan {$user->name} has submitted a verification application for shop '{$user->shopName}'.",
            'system',
            '/admin/sellers'
        );

        return redirect()->route('login')->with('success', 'Your artisan application has been submitted and is awaiting approval.');
    }

    public function logout(Request $request)
    {
        $cart = $request->session()->get('cart', []);

        // Persist final cart state to DB for the authenticated user before logging out
        $user = Auth::user();
        if ($user instanceof User && !empty($cart)) {
            $user->update(['cart' => json_encode($cart)]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Cart is intentionally NOT restored to the session — guests must log in to see/add to cart

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

            if ($user->status === 'frozen') {
                return back()->withErrors(['email' => 'Pay commission to continue']);
            }

            if ($user->status === 'blocked') {
                return back()->withErrors(['email' => 'Your account has been blocked. Reason: ' . ($user->violationReason ?? 'Policy violation')]);
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
        /** @var User $user */
        $user = Auth::user();
        $request->validate([
            'name'         => 'required|string|max:255',
            'username'     => 'required|string|max:255|unique:users,username,' . $user->id,
            'avatar'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'profilePhoto' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $user->name     = $request->name;
        $user->username = $request->username;

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/avatars'), $filename);
            $user->profilePhoto = '/uploads/avatars/' . $filename;
        } elseif ($request->hasFile('profilePhoto')) {
            $file = $request->file('profilePhoto');
            $filename = time() . '_' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/avatars'), $filename);
            $user->profilePhoto = '/uploads/avatars/' . $filename;
        }

        $user->save();

        return redirect()->route('profile')->with('success', 'Profile updated successfully!');
    }

    public function submitCommissionPayment(Request $request)
    {
        $request->validate([
            'email'            => 'required|email',
            'payment_method'   => 'required|string|in:GCash,Maya',
            'reference_number' => 'required|string|max:100',
            'proof_image'      => 'required|image|max:5000',
        ]);

        $user = User::where('email', strtolower($request->email))->first();

        if (!$user) {
            return back()->withErrors(['email' => 'User account not found.'])->withInput();
        }

        $proofPath = $request->file('proof_image')->store('payment_proofs', 'public');
        $currentPeriod = date('Y-m');

        CommissionRecord::updateOrCreate(
            ['sellerId' => $user->id, 'period' => $currentPeriod],
            [
                'paymentMethod'   => $request->payment_method,
                'referenceNumber' => $request->reference_number,
                'paymentProof'    => $proofPath,
            ]
        );

        return back()->with('payment_submitted', 'Your payment proof and reference number have been submitted successfully! Super Admin will verify and unfreeze your account soon.');
    }

    public function addresses()
    {
        $user = Auth::user();
        return view('profile.addresses', compact('user'));
    }

    public function changePasswordPage()
    {
        $user = Auth::user();
        return view('profile.change-password', compact('user'));
    }

    public function changePassword(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.'])->withInput();
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return redirect()->route('profile.change-password')->with('success', 'Password changed successfully!');
    }
}

