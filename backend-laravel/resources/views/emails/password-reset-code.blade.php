@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName ?? 'User' }}!</div>
<p>We received a request to reset the password for your LumBarong account. Use the 6-digit verification code below to set a new password:</p>

<div class="code-box">
    <div class="code-number">{{ $code }}</div>
    <div class="code-expiry">⏱️ This password reset code will expire in 10 minutes.</div>
</div>

<p>For your security, this code is strictly for resetting your existing account password and cannot be used for account registration or verification.</p>
<div class="button-wrapper">
    <a href="{{ url('/forgot-password/verify') }}" class="btn-primary">Reset Password</a>
</div>
@endsection
