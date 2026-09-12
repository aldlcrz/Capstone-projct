@extends('emails.layout')

@section('content')
<div class="greeting">Security Alert: Email Address Change Requested</div>
<p>Hello <strong>{{ $userName }}</strong>,</p>
<p>A request was submitted to change the primary email address for your LumBarong account. To authorize this change, please enter the following 6-digit confirmation code on your profile security screen:</p>

<div class="code-box">
    <div class="code-number">{{ $code }}</div>
    <div class="code-expiry">⏱️ Valid for 10 minutes.</div>
</div>

<p>If you did not make this request, your account may be compromised. Please sign in and secure your password immediately.</p>
@endsection
