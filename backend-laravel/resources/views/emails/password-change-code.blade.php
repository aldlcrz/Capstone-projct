@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName ?? 'User' }}!</div>
<p>You requested to change the password for your LumBarong account. To verify that this request originated from you, enter the 6-digit security verification code below:</p>

<div class="code-box">
    <div class="code-number">{{ $code }}</div>
    <div class="code-expiry">⏱️ This security code will expire in 10 minutes.</div>
</div>

<p>If you did not request to change your password, please sign in to your LumBarong account immediately and review your active sessions, or contact support.</p>
<div class="button-wrapper">
    <a href="{{ url('/profile/change-password') }}" class="btn-primary">Go to Change Password</a>
</div>
@endsection
