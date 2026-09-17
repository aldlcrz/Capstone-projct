@extends('emails.layout')

@section('content')
<div class="greeting">Password Reset Request</div>

<p style="margin: 0 0 16px 0; color: #483E35; font-size: 14.5px; line-height: 1.75;">
    Hello <strong>{{ $userName ?? 'Esteemed Patron' }}</strong>,
</p>
<p style="margin: 0 0 16px 0; color: #483E35; font-size: 14.5px; line-height: 1.75;">
    We received a request to reset the password for your LumBarong account. Please enter the following 6-digit security code on the password reset screen:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 10 minutes',
    'vaultLabel' => 'Password Reset Passkey'
])

<div class="security-notice-box">
    <strong style="color: #2D231B;">Security Notice:</strong> If you did not request a password reset, please ensure your account credentials are safe. No changes have been made to your account.
</div>
@endsection
