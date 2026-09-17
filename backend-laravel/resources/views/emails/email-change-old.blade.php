@extends('emails.layout')

@section('content')
<div class="greeting">Security Alert: Email Address Change</div>

<span class="badge badge-warning">🛡️ Authorization Required</span>

<p style="margin: 0 0 16px 0; color: #483E35; font-size: 14.5px; line-height: 1.75;">
    Hello <strong>{{ $userName ?? 'Esteemed Patron' }}</strong>,
</p>
<p style="margin: 0 0 16px 0; color: #483E35; font-size: 14.5px; line-height: 1.75;">
    A request was submitted to change the primary email address on your LumBarong account. To authorize this change, please enter the following 6-digit confirmation code on your profile security screen:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 10 minutes',
    'vaultLabel' => 'Authorization Passkey'
])

<div class="security-notice-box">
    <strong style="color: #2D231B;">Immediate Action Needed:</strong> If you did not make this request, your account credentials may be at risk. Please sign in and update your password immediately.
</div>
@endsection
