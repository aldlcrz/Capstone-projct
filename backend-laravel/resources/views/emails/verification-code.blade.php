@extends('emails.layout')

@section('content')
<span class="verified-pill">✓ Verified LumBarong Account</span>

<div class="greeting">Hello, {{ $userName ?? 'Esteemed Guest' }}!</div>

<p class="body-text">
    Thank you for registering with <strong>LumBarong</strong>. To complete your account activation and access your artisan marketplace dashboard, please enter the unique 6-digit authentication code below:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 5 minutes',
    'vaultLabel' => 'Account Verification Code'
])

<div class="security-notice-box">
    <strong style="color: #1E1915;">Security Notice:</strong> If you did not initiate this registration with LumBarong, please disregard this email. Your email address and credentials remain completely secure.
</div>
@endsection
