@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName ?? 'User' }}!</div>
<p>We received a request to change the registered email address of your LumBarong account to <strong>{{ $newEmail }}</strong>.</p>
<p>For your security, we require verification of your current email address before proceeding. Please enter the 6-digit verification code below:</p>

<div class="code-box">
    <div class="code-number">{{ $code }}</div>
    <div class="code-expiry">⏱️ This security code will expire in 10 minutes.</div>
</div>

<p><strong>⚠️ Security Warning:</strong> If you did NOT request this email change, please do not share this code with anyone. Log in to your LumBarong account immediately and change your password, or contact support.</p>
@endsection
