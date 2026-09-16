@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $customerName ?? ($userName ?? 'Valued Customer') }}!</div>
<span class="badge badge-info">📦 Order Update</span>
<p>Your order <strong>#{{ $orderId }}</strong> has been updated to: <strong style="text-transform: uppercase; color: #C0420A;">{{ $status }}</strong>.</p>

@if(!empty($statusMessage))
<p style="background: #f1f5f9; padding: 10px 14px; border-radius: 8px; font-size: 13px; color: #334155; margin: 12px 0;">
    <strong>Details:</strong> {{ $statusMessage }}
</p>
@endif

<p style="margin-top: 14px; font-size: 13px; color: #475569;">
    @if(strtolower($status) === 'shipped' || strtolower($status) === 'in transit')
        Your handcrafted item is on its way to your delivery address.
    @elseif(strtolower($status) === 'delivered' || strtolower($status) === 'completed')
        Your parcel has been delivered. Thank you for supporting authentic Philippine craftsmanship!
    @else
        Your artisan is currently preparing your handcrafted garment.
    @endif
</p>
@endsection
