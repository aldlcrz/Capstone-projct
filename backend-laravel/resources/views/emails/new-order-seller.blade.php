@extends('emails.layout')

@section('content')
<div class="greeting">🎉 You Have a New Order!</div>
<span class="badge badge-success">New Order #{{ $orderId }}</span>
<p>Hello {{ $sellerName }},</p>
<p>A customer has placed a new order in your shop! Total Order Amount: <strong>₱{{ number_format($totalAmount, 2) }}</strong>.</p>
<p>Please log in to your LumBarong Seller Center to review and process the order.</p>

<div class="button-wrapper">
    <a href="{{ url('/seller/orders') }}" class="btn-primary">Manage Seller Orders</a>
</div>
@endsection
