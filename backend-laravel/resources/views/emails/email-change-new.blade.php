@extends('emails.layout')

@section('content')
<span class="badge badge-info">✉️ New Email Verification</span>

<div class="greeting">Verify Your New Email Address</div>

<p class="body-text">
    Hello <strong>{{ $userName ?? 'Esteemed Patron' }}</strong>,
</p>
<p class="body-text">
    Please use the following 6-digit confirmation code on your verification screen to confirm ownership of this new email address:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 10 minutes',
    'vaultLabel' => 'Email Verification Passkey'
])

<div class="security-notice-box">
    <strong style="color: #1E1915;">Security Notice:</strong> If you did not request to link this email to a LumBarong account, please ignore this message.
</div>
@endsection
