@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName ?? 'Artisan / Customer' }}!</div>
<p>Thank you for signing up with LumBarong. To complete your account activation, please enter the unique 6-digit verification code below:</p>

<div class="code-box">
    <div class="code-number">{{ $code }}</div>
    <div class="code-expiry">⏱️ This code will expire in 10 minutes.</div>
</div>

<p>If you did not initiate this registration, please ignore this email.</p>
<div class="button-wrapper">
    <a href="{{ url('/verify-email') }}" class="btn-primary">Verify Account Now</a>
</div>
@endsection
