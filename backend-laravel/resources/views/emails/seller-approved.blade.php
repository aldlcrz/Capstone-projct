@extends('emails.layout')

@section('content')
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 4px;">
    <tr>
        <td style="width: 44px; vertical-align: middle; padding-right: 14px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" style="width: 44px; height: 44px; background-color: #FAF5EA; border: 1px solid #EADDC8; border-radius: 12px; text-align: center; vertical-align: middle;">
                        <span style="font-size: 20px; line-height: 44px; display: inline-block;">🎉</span>
                    </td>
                </tr>
            </table>
        </td>
        <td style="vertical-align: middle;">
            <h2 class="greeting">Mabuhay, {{ $sellerName ?? ($userName ?? 'Artisan') }}!</h2>
        </td>
    </tr>
</table>

<div style="margin: 12px 0 16px 0;">
    <span class="badge badge-success">Application Approved &amp; Verified</span>
</div>

<p class="body-text">
    Congratulations! Your artisan workshop <strong>{{ $shopName }}</strong> has been verified and approved by the LumBarong Administration team.
</p>
<p class="body-text">
    Your shop is now active on the marketplace. You can now manage artisan product listings, set up custom embroidery options, and fulfill customer orders through your Seller Control Panel.
</p>
<p style="font-size: 12px; color: #78716C; margin-top: 20px;">
    Welcome to the LumBarong artisan community representing Lumban, Laguna!
</p>
@endsection
