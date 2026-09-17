@extends('emails.layout')

@section('content')
<span class="badge badge-warning">🛡️ Password Verification</span>

<div class="greeting">Password Change Verification</div>

<p class="body-text">
    Hello <strong>{{ $userName ?? 'Esteemed Patron' }}</strong>,
</p>
<p class="body-text">
    You requested to update the password for your LumBarong account. Please enter the following 6-digit confirmation code on your verification screen:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 10 minutes',
    'vaultLabel' => 'Password Change Passkey'
])

<div class="security-notice-box">
    <strong style="color: #1E1915;">Security Notice:</strong> If you did not request this password change, please review your account security immediately.
</div>
@endsection
