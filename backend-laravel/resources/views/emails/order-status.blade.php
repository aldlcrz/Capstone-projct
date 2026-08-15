@extends('emails.layout')

@section('content')
<div class="greeting">Order Status Update</div>
<span class="badge badge-info">{{ $status }}</span>
<p>Hello {{ $customerName }},</p>
<p>The status of your order <strong>#{{ $orderId }}</strong> has been updated by the seller to <strong>{{ $status }}</strong>.</p>
<p><em>{{ $statusMessage ?? 'Check your order status and details in your account dashboard.' }}</em></p>

<div class="button-wrapper">
    <a href="{{ url('/orders/' . $orderId) }}" class="btn-primary">View Order Details</a>
</div>
@endsection
