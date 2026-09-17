@extends('emails.layout')

@section('content')
<div class="greeting">Password Change Verification</div>

<p style="margin: 0 0 16px 0; color: #483E35; font-size: 14.5px; line-height: 1.75;">
    Hello <strong>{{ $userName ?? 'Esteemed Patron' }}</strong>,
</p>
<p style="margin: 0 0 16px 0; color: #483E35; font-size: 14.5px; line-height: 1.75;">
    You requested to update the password for your LumBarong account. Please enter the following 6-digit confirmation code on your verification screen:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 10 minutes',
    'vaultLabel' => 'Password Change Passkey'
])

<div class="security-notice-box">
    <strong style="color: #2D231B;">Security Notice:</strong> If you did not request this password change, please review your account security immediately.
</div>
@endsection
