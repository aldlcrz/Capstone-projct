@extends('emails.layout')

@section('content')
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 4px;">
    <tr>
        <td style="width: 44px; vertical-align: middle; padding-right: 14px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" style="width: 44px; height: 44px; background-color: #FAF5EA; border: 1px solid #EADDC8; border-radius: 12px; text-align: center; vertical-align: middle;">
                        <span style="font-size: 20px; line-height: 44px; display: inline-block;">🛡️</span>
                    </td>
                </tr>
            </table>
        </td>
        <td style="vertical-align: middle;">
            <h2 class="greeting">Password Change Verification</h2>
        </td>
    </tr>
</table>

<p class="body-text">
    Hello <strong>{{ $userName ?? 'Esteemed Patron' }}</strong>, you requested to update the password for your LumBarong account. Please enter the following 6-digit confirmation code on your verification screen:
</p>

@include('emails.partials.code-vault', [
    'code' => $code,
    'expiryText' => 'Valid for 10 minutes',
    'vaultLabel' => 'PASSWORD CHANGE CODE'
])

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
                <div style="font-size: 12px; color: #78716C; line-height: 1.5;">If you did not request this password change, please review your account security immediately.</div>
            </td>
        </tr>
    </table>
</div>
@endsection
