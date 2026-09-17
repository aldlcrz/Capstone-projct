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
            <h2 class="greeting">Mabuhay, {{ $sellerName }}!</h2>
        </td>
    </tr>
</table>

<div style="margin: 12px 0 16px 0;">
    <span class="badge badge-success">New Order Received</span>
</div>

<p class="body-text">
    You have received a new customer order <strong>#{{ $orderId }}</strong> for <strong>₱{{ number_format($totalAmount, 2) }}</strong>.
</p>

@if(!empty($customerName))
<div style="background: #FAF8F5; border: 1px solid #ECE3D2; border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #5A524A; margin: 14px 0;">
    Customer Name: <strong>{{ $customerName }}</strong>
</div>
@endif

<p style="margin-top: 14px; font-size: 13px; color: #78716C; line-height: 1.6;">
    Please log in to your Seller Control Panel to review the order items, verify the payment receipt, and begin artisan preparation.
</p>
@endsection
