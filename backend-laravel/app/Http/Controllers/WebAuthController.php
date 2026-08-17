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
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Please enter your password.',
        ]);

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors([
                'email' => 'This email is not registered yet.',
            ])->onlyInput('email');
        }

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'Incorrect password. Please try again.',
            ])->onlyInput('email');
        }

        if (Auth::attempt(['email' => $email, 'password' => $request->password])) {
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

            // Seller whose email is verified but still awaiting admin approval
            if ($user->role === 'seller' && $user->status === 'pending_approval') {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your artisan application is still awaiting admin approval. You will be notified once it is reviewed.',
                ])->onlyInput('email');
            }

            if (!$user->isVerified) {
                Auth::logout();
                session(['verify_email' => $user->email]);

                $existing = \App\Models\EmailVerification::where('email', $user->email)->where('type', 'registration')->first();
                $shouldSend = true;
                if ($existing && $existing->last_sent_at && $existing->last_sent_at->diffInSeconds(now()) < 60) {
                    $shouldSend = false;
                }

                if ($shouldSend) {
                    $verification = \App\Services\EmailNotificationService::createVerificationCode($user->email, 'registration');
                    $mailable = new \App\Mail\VerificationCodeMail($user->name, $verification->code);
                    \App\Services\EmailNotificationService::sendNotification($user->email, $mailable, 'email_verification', $user->id, 'User', $user->id);
                    return redirect()->route('verify.email')->with('success', 'A 6-digit verification code has been sent to your Gmail. Please enter it below to activate your account.');
                }

                return redirect()->route('verify.email')->with('info', 'A verification code was recently sent to your Gmail address. Please check your inbox or spam folder.');
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

            // Restore guest customer pending context (Add to cart / Buy now / Wishlist / Checkout restoration)
            $contextRedirect = $this->restorePendingContext($user, $request);
            if ($contextRedirect) return $contextRedirect;

            return redirect()->intended('/');
        }

        return back()->withErrors([
            'email' => 'Unable to sign in with these credentials.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return response()
            ->view('auth.register')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
    }

    public function register(Request $request)
    {
        $email = strtolower(trim($request->email));
        $username = trim($request->username);

        // Delete any stale unverified user record with this email so it doesn't block re-registering
        $staleUser = User::where('email', $email)->where('isVerified', false)->first();
        if ($staleUser) {
            $staleUser->delete();
        }

        $validator = Validator::make($request->all(), [
            'username'      => 'required|string|min:3|max:50|unique:users,username',
            'email'         => 'required|string|email|max:255|unique:users,email',
            'password'      => [
                'required',
                'string',
                'min:6',
                'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/',
                'confirmed',
            ],
            'terms_consent' => 'required|accepted',
        ], [
            'username.required'      => 'Please choose a username.',
            'username.min'           => 'Username must be at least 3 characters.',
            'username.unique'        => 'This username is already taken.',
            'email.required'         => 'Please enter your email address.',
            'email.email'            => 'Please enter a valid email address.',
            'email.unique'           => 'This email is already in use. Please log in instead.',
            'password.required'      => 'Please enter a password.',
            'password.min'           => 'Password must be at least 6 characters.',
            'password.regex'         => 'Password must contain at least one letter and one number.',
            'password.confirmed'     => 'Password confirmation does not match.',
            'terms_consent.required' => 'You must accept the Terms and Conditions to proceed.',
            'terms_consent.accepted' => 'You must accept the Terms and Conditions to proceed.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $googleSignup = session('google_signup');
        $googleId = null;
        $profilePhoto = null;
        if ($googleSignup && strtolower(trim($googleSignup['email'] ?? '')) === $email) {
            $googleId = $googleSignup['googleId'] ?? null;
            $profilePhoto = $googleSignup['picture'] ?? null;
        }

        // Account is NOT created in DB until verification code is entered!
        session([
            'pending_registration' => [
                'name'              => $request->name ?: ($googleSignup['name'] ?? $username),
                'username'          => $username,
                'email'             => $email,
                'password'          => Hash::make($request->password),
                'role'              => 'customer',
                'status'            => 'active',
                'isVerified'        => true,
                'googleId'          => $googleId,
                'profilePhoto'      => $profilePhoto,
                'hasPasswordSet'    => true,
            ],
            'verify_email' => $email,
        ]);
        session()->forget('google_signup');

        // Generate verification code and send email
        $verification = \App\Services\EmailNotificationService::createVerificationCode($email, 'registration');
        $mailable = new \App\Mail\VerificationCodeMail($username, $verification->code);
        $sent = \App\Services\EmailNotificationService::sendNotification($email, $mailable, 'email_verification');

        if (!$sent) {
            return redirect()->route('verify.email')->with('warning', 'Verification code created, but sending email may be delayed. Please check your Gmail or click Resend.');
        }

        return redirect()->route('verify.email')->with('success', 'Verification code sent to your Gmail! Enter the 6-digit code below to create and activate your account.');
    }

    public function showSellerRegister()
    {
        return response()
            ->view('auth.seller-register')
            ->header('Cache-Control', 'no-cache, no-store, max-age=0, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sun, 02 Jan 1990 00:00:00 GMT');
    }

    public function sellerRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'                 => 'required|string|max:255',
            'email'                => 'required|string|email|max:255|unique:users,email',
            'password'             => 'required|string|min:6|confirmed',
            'mobileNumber'         => 'required|string|max:20',
            'gcashNumber'          => 'nullable|string|max:20',
            'residencyCertificate' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'birDocument'           => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'shopName'             => 'nullable|string|max:255',
            'shopAddress'          => 'nullable|string|max:1000',
            'businessPermit'       => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'terms_consent'        => 'required|accepted',
        ], [
            'email.unique'          => 'This Gmail address is already registered. Please log in or use a different address.',
            'terms_consent.required' => 'You must accept the Terms and Conditions and Privacy Policy to proceed.',
            'terms_consent.accepted' => 'You must accept the Terms and Conditions and Privacy Policy to proceed.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = strtolower(trim($request->email));
        $googleSignup = session('google_seller_signup');
        $googleId = null;
        $profilePhoto = null;
        if ($googleSignup && strtolower(trim($googleSignup['email'] ?? '')) === $email) {
            $googleId = $googleSignup['googleId'] ?? null;
            $profilePhoto = $googleSignup['picture'] ?? null;
        }

        $data = [
            'name'         => $request->name,
            'email'        => $email,
            'password'     => Hash::make($request->password),
            'mobileNumber' => $request->mobileNumber,
            'gcashNumber'  => $request->gcashNumber,
            'shopName'     => $request->shopName ?? $request->name . "'s Workshop",
            'shopAddress'  => $request->shopAddress ?? 'Not Provided',
            'role'         => 'seller',
            'status'       => 'active',
            'isVerified'   => false, // Requires Gmail verification & admin approval
            'googleId'     => $googleId,
            'profilePhoto' => $profilePhoto,
        ];

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
        session()->forget('google_seller_signup');

        // Generate verification code and send email
        $verification = \App\Services\EmailNotificationService::createVerificationCode($email, 'registration');
        $mailable = new \App\Mail\VerificationCodeMail($user->name, $verification->code);
        \App\Services\EmailNotificationService::sendNotification($email, $mailable, 'email_verification', $user->id, 'User', $user->id);

        \App\Models\Notification::sendToAdmins(
            'New Seller Application',
            "Artisan {$user->name} has submitted a verification application for shop '{$user->shopName}'.",
            'system',
            '/admin/sellers'
        );

        session(['verify_email' => $email]);

        return redirect()->route('verify.email')->with('success', 'Application submitted! Please verify your Gmail address to proceed.');
    }

    public function showVerifyEmail(Request $request)
    {
        if ($request->filled('email')) {
            session(['verify_email' => strtolower(trim($request->email))]);
        }
        $email = session('verify_email') ?? (Auth::check() ? Auth::user()->email : null);
        return view('auth.verify-email', compact('email'));
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $email = strtolower(trim($request->email));
        $isValid = \App\Services\EmailNotificationService::verifyCode($email, $request->code, 'registration');

        if (!$isValid) {
            return back()->withErrors(['code' => 'Invalid or expired verification code. Please request a new code if expired.']);
        }

        // 1. Create account from session if pending registration exists
        $pending = session('pending_registration');
        $user = null;
        if ($pending && isset($pending['email']) && strtolower($pending['email']) === $email) {
            $user = User::create($pending);
            session()->forget('pending_registration');
        } else {
            // 2. Fallback to existing unverified user record (e.g. seller registration)
            $user = User::where('email', $email)->first();
            if ($user) {
                if ($user->role === 'seller') {
                    // Seller email verified — keep isVerified=false until admin approves
                    $user->status = 'pending_approval';
                } else {
                    $user->isVerified = true;
                }
                $user->save();
            }
        }

        if ($user) {
            \App\Services\EmailNotificationService::consumeCode($email, 'registration');
            Auth::login($user);

            $contextRedirect = $this->restorePendingContext($user, $request);
            if ($contextRedirect) return $contextRedirect;

            return redirect('/')->with('success', 'Your Gmail address has been verified and your account is now created!');
        }

        return redirect()->route('register')->with('error', 'Registration session expired. Please register again.');
    }

    public function resendVerificationCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $email = strtolower(trim($request->email));

        $pending = session('pending_registration');
        $user = User::where('email', $email)->first();

        $userName = 'Customer';
        $userId = null;

        if ($pending && isset($pending['email']) && strtolower($pending['email']) === $email) {
            $userName = $pending['name'] ?? 'Customer';
        } elseif ($user) {
            $userName = $user->name;
            $userId = $user->id;
        } else {
            return back()->withErrors(['email' => 'No pending registration found for this Gmail address. Please register first.']);
        }

        $existing = \App\Models\EmailVerification::where('email', $email)->where('type', 'registration')->first();
        if ($existing && $existing->last_sent_at && $existing->last_sent_at->diffInSeconds(now()) < 60) {
            $secondsLeft = 60 - $existing->last_sent_at->diffInSeconds(now());
            return back()->withErrors(['code' => "Please wait {$secondsLeft} seconds before requesting a new code."]);
        }

        $verification = \App\Services\EmailNotificationService::createVerificationCode($email, 'registration');
        $mailable = new \App\Mail\VerificationCodeMail($userName, $verification->code);
        $sent = \App\Services\EmailNotificationService::sendNotification($email, $mailable, 'email_verification', $userId, 'User', $userId);

        if (!$sent) {
            return back()->withErrors(['code' => 'Unable to send verification code to your Gmail address at this time. Please try again.']);
        }

        session(['verify_email' => $email]);

        return back()->with('success', 'A new 6-digit verification code has been sent to your Gmail address.');
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
            $clientId = config('services.google.client_id');
            if (empty($clientId)) {
                return back()->withErrors(['email' => 'Google Login is not configured on the server (Missing GOOGLE_CLIENT_ID). Please log in with your email & password.']);
            }

            $client = new \Google\Client(['client_id' => $clientId]);
            $payload = $client->verifyIdToken($credential);

            if (!$payload) {
                return back()->withErrors(['email' => 'Google authentication failed. Please try again.']);
            }

            $email = strtolower(trim($payload['email'] ?? ''));
            $googleId = $payload['sub'] ?? null;
            $name = $payload['name'] ?? 'Customer';
            $picture = $payload['picture'] ?? null;

            if (empty($email)) {
                return back()->withErrors(['email' => 'Unable to retrieve email from your Google account.']);
            }

            $user = User::where('email', $email)
                ->orWhere('googleId', $googleId)
                ->first();

            // If account is NOT in database, suggest sign-up and prefill their Google info
            if (!$user) {
                session([
                    'google_signup' => [
                        'email'    => $email,
                        'name'     => $name,
                        'googleId' => $googleId,
                        'picture'  => $picture,
                    ]
                ]);
                return redirect()->route('register')->with('info', 'No account found with this Google email. Please complete the form below and set a password to create your account.');
            }

            // If user exists, attach googleId if not yet set
            if (!$user->googleId && $googleId) {
                $user->googleId = $googleId;
                $user->save();
            }

            if ($user->status === 'frozen') {
                return back()->withErrors(['email' => 'Pay commission to continue']);
            }

            if ($user->status === 'blocked') {
                return back()->withErrors(['email' => 'Your account has been blocked. Reason: ' . ($user->violationReason ?? 'Policy violation')]);
            }

            if ($user->status === 'rejected') {
                return back()->withErrors(['email' => 'Your account has been rejected. Reason: ' . ($user->rejectionReason ?? 'Did not meet requirements')]);
            }

            if ($user->role === 'seller' && $user->status === 'pending_approval') {
                return back()->withErrors(['email' => 'Your artisan application is still awaiting admin approval. You will be notified once it is reviewed.']);
            }

            if (!$user->isVerified) {
                session(['verify_email' => $user->email]);

                $existing = \App\Models\EmailVerification::where('email', $user->email)->where('type', 'registration')->first();
                $shouldSend = true;
                if ($existing && $existing->last_sent_at && $existing->last_sent_at->diffInSeconds(now()) < 60) {
                    $shouldSend = false;
                }

                if ($shouldSend) {
                    $verification = \App\Services\EmailNotificationService::createVerificationCode($user->email, 'registration');
                    $mailable = new \App\Mail\VerificationCodeMail($user->name, $verification->code);
                    \App\Services\EmailNotificationService::sendNotification($user->email, $mailable, 'email_verification', $user->id, 'User', $user->id);
                    return redirect()->route('verify.email')->with('success', 'A 6-digit verification code has been sent to your Gmail. Please enter it below to activate your account.');
                }

                return redirect()->route('verify.email')->with('info', 'A verification code was recently sent to your Gmail address. Please check your inbox or spam folder.');
            }

            Auth::login($user);
            $request->session()->regenerate();

            if ($user->role === 'superadmin') return redirect()->route('superadmin.dashboard');
            if ($user->role === 'admin') return redirect()->route('admin.dashboard');
            if ($user->role === 'seller') return redirect()->route('seller.dashboard');

            $contextRedirect = $this->restorePendingContext($user, $request);
            if ($contextRedirect) return $contextRedirect;

            return redirect()->intended('/');
        } catch (\Exception $e) {
            \Log::error('Google Login Error: ' . $e->getMessage());
            return back()->withErrors(['email' => 'An error occurred during Google authentication. Please try again.']);
        }
    }

    public function handleGoogleSignup(Request $request)
    {
        try {
            $credential = $request->credential;
            $clientId = config('services.google.client_id');
            if (empty($clientId)) {
                return back()->withErrors(['email' => 'Google Sign-up is not configured on the server (Missing GOOGLE_CLIENT_ID). Please register with the form below.']);
            }

            $client = new \Google\Client(['client_id' => $clientId]);
            $payload = $client->verifyIdToken($credential);

            if (!$payload) {
                return back()->withErrors(['email' => 'Google authentication failed. Please try again.']);
            }

            $email = strtolower(trim($payload['email'] ?? ''));
            $googleId = $payload['sub'] ?? null;
            $name = $payload['name'] ?? 'Customer';
            $picture = $payload['picture'] ?? null;

            if (empty($email)) {
                return back()->withErrors(['email' => 'Unable to retrieve email from your Google account.']);
            }

            $user = User::where('email', $email)
                ->orWhere('googleId', $googleId)
                ->first();

            // If account ALREADY exists, tell them to log in
            if ($user) {
                return redirect()->route('login')->with('info', 'An account with this Google email already exists. Please log in with your credentials or Google sign-in.');
            }

            // Save Google profile to prefill the registration form
            session([
                'google_signup' => [
                    'email'    => $email,
                    'name'     => $name,
                    'googleId' => $googleId,
                    'picture'  => $picture,
                ]
            ]);

            return redirect()->route('register')->with('success', 'Google account connected! Please choose your username and password below to finish creating your account.');
        } catch (\Exception $e) {
            \Log::error('Google Signup Error: ' . $e->getMessage());
            return back()->withErrors(['email' => 'An error occurred during Google authentication. Please try again.']);
        }
    }

    public function handleGoogleSellerSignup(Request $request)
    {
        try {
            $credential = $request->credential;
            $clientId = config('services.google.client_id');
            if (empty($clientId)) {
                return back()->withErrors(['email' => 'Google Sign-up is not configured on the server (Missing GOOGLE_CLIENT_ID). Please register with the form below.']);
            }

            $client = new \Google\Client(['client_id' => $clientId]);
            $payload = $client->verifyIdToken($credential);

            if (!$payload) {
                return back()->withErrors(['email' => 'Google authentication failed. Please try again.']);
            }

            $email = strtolower(trim($payload['email'] ?? ''));
            $googleId = $payload['sub'] ?? null;
            $name = $payload['name'] ?? 'Artisan Seller';
            $picture = $payload['picture'] ?? null;

            if (empty($email)) {
                return back()->withErrors(['email' => 'Unable to retrieve email from your Google account.']);
            }

            $user = User::where('email', $email)
                ->orWhere('googleId', $googleId)
                ->first();

            // If account ALREADY exists, redirect to login
            if ($user) {
                return redirect()->route('login')->with('info', 'An account with this Google email already exists. Please log in with your credentials or Google sign-in.');
            }

            // Save Google profile to prefill the seller registration form
            session([
                'google_seller_signup' => [
                    'email'    => $email,
                    'name'     => $name,
                    'googleId' => $googleId,
                    'picture'  => $picture,
                ]
            ]);

            return redirect()->route('seller.register')->with('success', 'Google account connected! Your name and email have been filled in. Please set your password and complete your artisan workshop requirements.');
        } catch (\Exception $e) {
            \Log::error('Google Seller Signup Error: ' . $e->getMessage());
            return back()->withErrors(['email' => 'An error occurred during Google authentication. Please try again.']);
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
        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return back()->withErrors(['email' => 'No registered account found with that Gmail address.']);
        }

        // Throttle resend if a code was recently sent within 60 seconds
        $existing = \App\Models\EmailVerification::where('email', $email)->where('type', 'password_reset')->first();
        if ($existing && $existing->last_sent_at && $existing->last_sent_at->diffInSeconds(now()) < 60) {
            $secondsLeft = 60 - $existing->last_sent_at->diffInSeconds(now());
            session(['reset_email' => $email]);
            return redirect()->route('password.verify.code')->withErrors(['code' => "A code was recently sent. Please wait {$secondsLeft} seconds before requesting another code."]);
        }

        $verification = \App\Services\EmailNotificationService::createVerificationCode($email, 'password_reset');
        $mailable = new \App\Mail\PasswordResetCodeMail($user->name, $verification->code);
        $sent = \App\Services\EmailNotificationService::sendNotification($email, $mailable, 'forgot_password', $user->id, 'User', $user->id);

        if (!$sent) {
            return back()->withErrors(['email' => 'We were unable to deliver the reset code to your Gmail address at this time. Please check your connection and try again.'])->withInput();
        }

        session(['reset_email' => $email]);

        return redirect()->route('password.verify.code')->with('success', 'A 6-digit password reset code has been sent to your Gmail address.');
    }

    public function showVerifyResetCode(Request $request)
    {
        if ($request->filled('email')) {
            session(['reset_email' => strtolower(trim($request->email))]);
        }
        $email = session('reset_email');
        return view('auth.verify-reset-code', compact('email'));
    }

    public function verifyResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code'  => 'required|string|size:6',
        ]);

        $email = strtolower(trim($request->email));
        $isValid = \App\Services\EmailNotificationService::verifyCode($email, $request->code, 'password_reset');

        if (!$isValid) {
            return back()->withErrors(['code' => 'Invalid or expired password reset code (maximum 5 attempts allowed per code).']);
        }

        session(['validated_reset_email' => $email]);

        return redirect()->route('password.reset.new')->with('success', 'Code verified! Please set your new password.');
    }

    public function showResetPassword()
    {
        if (!session('validated_reset_email')) {
            return redirect()->route('password.request')->with('error', 'Session expired. Please request a password reset code again.');
        }
        return view('auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $email = session('validated_reset_email');
        if (!$email) {
            return redirect()->route('password.request')->with('error', 'Security session expired. Please start the password reset process again.');
        }

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.required' => 'Please enter your new password.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('password.request')->with('error', 'User account not found.');
        }

        $user->password = Hash::make($request->password);
        $user->save();

        \App\Services\EmailNotificationService::consumeCode($email, 'password_reset');
        session()->forget(['reset_email', 'validated_reset_email']);

        return redirect()->route('login')->with('success', 'Password updated successfully! Please log in with your new password.');
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
        return redirect()->route('profile', ['change_password' => 1]);
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

        return redirect()->route('profile')->with('success', 'Password changed successfully!');
    }

    /**
     * Restore previous guest customer context and execute pending action (Add to Cart / Buy Now / Wishlist) upon login/verification.
     */
    protected function restorePendingContext(User $user, Request $request)
    {
        $intent = session()->get('pending_intent') ?: $request->input('pending_intent');

        if (is_string($intent)) {
            $intent = json_decode($intent, true);
        }

        if (!empty($intent) && is_array($intent)) {
            $action    = $intent['action'] ?? 'add_to_cart';
            $productId = $intent['productId'] ?? null;
            $quantity  = (int) ($intent['quantity'] ?? 1);
            $size      = $intent['size'] ?? null;
            $variation = $intent['variation'] ?? null;
            $redirect  = $intent['redirectUrl'] ?? null;

            session()->forget('pending_intent');

            if ($productId) {
                $product = \App\Models\Product::with('seller')->find($productId);
                if ($product) {
                    if ($action === 'add_to_cart' || $action === 'buy_now') {
                        $cart = session()->get('cart', []);
                        $key  = $productId . '_' . ($size ?? '') . '_' . ($variation ?? '');

                        $availableStock = $product->stock;
                        if ($size && !empty($product->size_stocks) && isset($product->size_stocks[$size])) {
                            $availableStock = (int) $product->size_stocks[$size];
                        }

                        $newItem = [
                            'key'                 => $key,
                            'id'                  => $product->id,
                            'name'                => $product->name,
                            'price'               => $product->sale_price,
                            'image'               => $product->getImageUrl(),
                            'quantity'            => min(max($quantity, 1), max($availableStock, 1)),
                            'size'                => $size,
                            'variation'           => $variation,
                            'sellerId'            => $product->sellerId,
                            'shippingFee'         => $product->shippingFee ?? 0,
                            'original_price'      => $product->price,
                            'discount_percentage' => $product->discount_percentage,
                            'is_on_sale'          => $product->is_on_sale && ($product->discount_percentage > 0),
                            'category_name'       => $product->category->name ?? 'Traditional',
                            'shop_name'           => $product->seller ? ($product->seller->shopName ?: $product->seller->name ?: 'Lumban Heritage Shop') : 'Lumban Heritage Shop',
                        ];

                        if (isset($cart[$key])) {
                            $cart[$key]['quantity'] = min($cart[$key]['quantity'] + $quantity, max($availableStock, 1));
                        } else {
                            $cart[$key] = $newItem;
                        }

                        session()->put('cart', $cart);
                        $user->update(['cart' => json_encode($cart)]);

                        if ($action === 'buy_now') {
                            session()->put('buy_now_item', $newItem);
                            session()->flash('success', "Welcome back, {$user->name}! Restored your selection for \"{$product->name}\". Proceeding to checkout.");
                            return redirect('/checkout?mode=buy_now');
                        }

                        session()->flash('success', "Welcome back, {$user->name}! \"{$product->name}\" was automatically added to your cart.");
                        return redirect($redirect ?: '/cart');
                    }

                    if ($action === 'wishlist') {
                        \App\Models\Wishlist::firstOrCreate([
                            'user_id'    => $user->id,
                            'product_id' => $product->id,
                        ]);
                        session()->flash('success', "Welcome back, {$user->name}! Added \"{$product->name}\" to your wishlist.");
                        return redirect($redirect ?: '/wishlist');
                    }
                }
            }

            if ($action === 'chat') {
                $sellerId   = $intent['sellerId'] ?? null;
                $sellerName = $intent['sellerName'] ?? 'Artisan';
                if ($sellerId) {
                    session(['open_chat' => ['sellerId' => $sellerId, 'sellerName' => $sellerName]]);
                }
                session()->flash('success', "Welcome back, {$user->name}! Opening message box with {$sellerName}.");
                return redirect($redirect ?: ($sellerId ? "/shops/{$sellerId}" : '/'));
            }

            if ($action === 'view_shop') {
                $shopId = $intent['shopId'] ?? $intent['sellerId'] ?? null;
                session()->flash('success', "Welcome back, {$user->name}!");
                return redirect($shopId ? "/shops/{$shopId}" : ($redirect ?: '/'));
            }

            if (!empty($redirect)) {
                return redirect($redirect);
            }
        }

        return null;
    }
}

