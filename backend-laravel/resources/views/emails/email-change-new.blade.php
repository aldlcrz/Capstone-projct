@extends('emails.layout')

@section('content')
<div class="greeting">Verify Your New Email Address</div>
<p>Hello <strong>{{ $userName }}</strong>,</p>
<p>Please use the following 6-digit confirmation code on your verification screen to confirm that you own this new email address:</p>

<div class="code-box">
    <div class="code-number">{{ $code }}</div>
    <div class="code-expiry">⏱️ Valid for 10 minutes.</div>
</div>

<p>If you did not request to link this email to a LumBarong account, please ignore this message.</p>
@endsection
