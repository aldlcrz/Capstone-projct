@extends('emails.layout')

@section('content')
<span class="badge badge-warning">🔑 Account Security</span>

<div class="greeting">Password Reset Request</div>

<p class="body-text">
    Hello <strong>{{ $userName ?? 'Esteemed Patron' }}</strong>,
</p>
<p class="body-text">
    We received a request to reset the password for your LumBarong account. Please enter the following 6-digit security code on the password reset screen:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 10 minutes',
    'vaultLabel' => 'Password Reset Passkey'
])

<div class="security-notice-box">
    <strong style="color: #1E1915;">Security Notice:</strong> If you did not request a password reset, please ensure your account credentials are safe. No changes have been made to your account.
</div>
@endsection
