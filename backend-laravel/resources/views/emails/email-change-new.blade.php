@extends('emails.layout')

@section('content')
<div class="greeting">Verify Your New Email Address</div>

<p style="margin: 0 0 16px 0; color: #483E35; font-size: 14.5px; line-height: 1.75;">
    Hello <strong>{{ $userName ?? 'Esteemed Patron' }}</strong>,
</p>
<p style="margin: 0 0 16px 0; color: #483E35; font-size: 14.5px; line-height: 1.75;">
    Please use the following 6-digit confirmation code on your verification screen to confirm ownership of this new email address:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 10 minutes',
    'vaultLabel' => 'Email Verification Passkey'
])

<div class="security-notice-box">
    <strong style="color: #2D231B;">Security Notice:</strong> If you did not request to link this email to a LumBarong account, please ignore this message.
</div>
@endsection
