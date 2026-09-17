@extends('emails.layout')

@section('content')
<span class="badge badge-warning">🛡️ Authorization Required</span>

<div class="greeting">Security Alert: Email Address Change</div>

<p class="body-text">
    Hello <strong>{{ $userName ?? 'Esteemed Patron' }}</strong>,
</p>
<p class="body-text">
    A request was submitted to change the primary email address on your LumBarong account. To authorize this change, please enter the following 6-digit confirmation code on your profile security screen:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 10 minutes',
    'vaultLabel' => 'Authorization Passkey'
])

<div class="security-notice-box">
    <strong style="color: #1E1915;">Immediate Action Needed:</strong> If you did not make this request, your account credentials may be at risk. Please sign in and update your password immediately.
</div>
@endsection
