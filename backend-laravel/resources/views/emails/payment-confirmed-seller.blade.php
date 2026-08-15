@extends('emails.layout')

@section('content')
<div class="greeting">💳 Payment Confirmed</div>
<span class="badge badge-success">Order #{{ $orderId }}</span>
<p>Hello {{ $sellerName }},</p>
<p>Payment for order <strong>#{{ $orderId }}</strong> (₱{{ number_format($totalAmount, 2) }}) via {{ $paymentMethod }} has been confirmed. You may now proceed with preparing the order for shipment.</p>

<div class="button-wrapper">
    <a href="{{ url('/seller/orders') }}" class="btn-primary">View Order</a>
</div>
@endsection
