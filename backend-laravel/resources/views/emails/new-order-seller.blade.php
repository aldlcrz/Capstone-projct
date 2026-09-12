@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $sellerName }}! 🎉</div>
<span class="badge badge-success">New Order Received</span>
<p>You have received a new customer order <strong>#{{ $orderId }}</strong> for <strong>₱{{ number_format($totalAmount, 2) }}</strong>.</p>
<p>Customer Name: <strong>{{ $customerName }}</strong></p>
<p style="margin-top: 14px; font-size: 13px; color: #475569;">Please log in to your Seller Control Panel to review the order items, verify the payment receipt, and begin artisan preparation.</p>
@endsection
