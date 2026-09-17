@extends('emails.layout')

@section('content')
<table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 4px;">
    <tr>
        <td style="width: 44px; vertical-align: middle; padding-right: 14px;">
            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                <tr>
                    <td align="center" style="width: 44px; height: 44px; background-color: #FAF5EA; border: 1px solid #EADDC8; border-radius: 12px; text-align: center; vertical-align: middle;">
                        <span style="font-size: 20px; line-height: 44px; display: inline-block;">📦</span>
                    </td>
                </tr>
            </table>
        </td>
        <td style="vertical-align: middle;">
            <h2 class="greeting">Hello, {{ $customerName ?? ($userName ?? 'Valued Customer') }}!</h2>
        </td>
    </tr>
</table>

<div style="margin: 12px 0 16px 0;">
    <span class="badge badge-info">Order Update</span>
</div>

<p class="body-text">
    Your artisan order <strong>#{{ $orderId }}</strong> has been updated to: <strong style="text-transform: uppercase; color: #996515;">{{ $status }}</strong>.
</p>

@if(!empty($statusMessage))
<div style="background: #FAF8F5; border: 1px solid #ECE3D2; border-left: 3px solid #C49520; border-radius: 10px; padding: 12px 16px; font-size: 13px; color: #5A524A; margin: 16px 0;">
    <strong>Details:</strong> {{ $statusMessage }}
</div>
@endif

<p style="margin-top: 14px; font-size: 13px; color: #78716C; line-height: 1.6;">
    @if(strtolower($status) === 'shipped' || strtolower($status) === 'in transit')
        Your handcrafted barong item is on its way to your delivery address.
    @elseif(strtolower($status) === 'delivered' || strtolower($status) === 'completed')
        Your parcel has been delivered. Thank you for supporting authentic Philippine craftsmanship!
    @else
        Your artisan is currently preparing your handcrafted garment with traditional Lumban embroidery.
    @endif
</p>
@endsection
