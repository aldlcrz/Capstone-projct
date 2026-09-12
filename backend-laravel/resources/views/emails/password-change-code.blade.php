@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName ?? 'User' }}!</div>
<p>You requested to change the password for your LumBarong account. Please enter the following 6-digit confirmation code on your screen:</p>

<div class="code-box">
    <div class="code-number">{{ $code }}</div>
    <div class="code-expiry">⏱️ This code will expire in 10 minutes.</div>
</div>

<p>If you did not request this password change, please review your account security immediately.</p>
@endsection
