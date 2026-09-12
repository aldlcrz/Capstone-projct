@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName ?? 'User' }}!</div>
<p>We received a request to reset the password for your LumBarong account. Please enter the following 6-digit security code on the password reset screen:</p>

<div class="code-box">
    <div class="code-number">{{ $code }}</div>
    <div class="code-expiry">⏱️ This code will expire in 10 minutes.</div>
</div>

<p>If you did not request a password reset, please ensure your account credentials are safe. No changes have been made to your account.</p>
@endsection
