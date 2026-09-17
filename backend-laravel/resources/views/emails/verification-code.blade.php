@extends('emails.layout')

@section('content')
{{-- Header Greeting with Icon Box matching reference image --}}
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 4px;">
    <tr>
        <td style="width: 44px; vertical-align: middle; padding-right: 14px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" style="width: 44px; height: 44px; background-color: #FAF5EA; border: 1px solid #EADDC8; border-radius: 12px; text-align: center; vertical-align: middle;">
                        <span style="font-size: 20px; line-height: 44px; display: inline-block;">✉️</span>
                    </td>
                </tr>
            </table>
        </td>
        <td style="vertical-align: middle;">
            <h2 class="greeting">Hello, {{ $userName ?? 'Testing' }}!</h2>
        </td>
    </tr>
</table>

<p class="body-text">
    Thank you for registering with <strong>LumBarong</strong>. To complete your account activation and access your artisan marketplace dashboard, please enter the unique 6-digit verification code below:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 5 minutes',
    'vaultLabel' => 'ACCOUNT VERIFICATION CODE'
])

{{-- Security Notice Card matching reference image --}}
<div class="security-notice-box">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="width: 36px; vertical-align: top; padding-right: 12px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td align="center" style="width: 36px; height: 36px; background-color: #FAF3E5; border-radius: 50%; text-align: center; vertical-align: middle;">
                            <span style="font-size: 16px; line-height: 36px; display: inline-block;">🛡️</span>
                        </td>
                    </tr>
                </table>
            </td>
            <td style="vertical-align: middle;">
                <div style="font-size: 13px; font-weight: 700; color: #1E1915; margin-bottom: 2px;">Security Notice:</div>
                <div style="font-size: 12px; color: #78716C; line-height: 1.5;">If you did not initiate this registration with LumBarong, please disregard this email. Your email address remains secure.</div>
            </td>
        </tr>
    </table>
</div>
@endsection
