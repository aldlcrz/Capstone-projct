@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName ?? 'User' }}!</div>
<p>You are setting this email address as your new primary account email for LumBarong.</p>
<p>To confirm that you own this email address and finalize the update, please enter the 6-digit verification code below:</p>

<div class="code-box">
    <div class="code-number">{{ $code }}</div>
    <div class="code-expiry">⏱️ This verification code will expire in 10 minutes.</div>
</div>

<p>Once verified, this will become your official login and notification email on LumBarong.</p>
@endsection
