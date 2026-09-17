@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName ?? 'Esteemed Guest' }}!</div>

<p style="margin: 0 0 16px 0; color: #483E35; font-size: 14.5px; line-height: 1.75;">
    Thank you for registering with <strong>LumBarong</strong>. To complete your account activation and access your artisan marketplace dashboard, please enter the unique 6-digit verification code below:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 5 minutes',
    'vaultLabel' => 'Account Verification Code'
])

<div class="security-notice-box">
    <strong style="color: #2D231B;">Security Notice:</strong> If you did not initiate this registration with LumBarong, please disregard this email. Your email address remains secure.
</div>
@endsection
