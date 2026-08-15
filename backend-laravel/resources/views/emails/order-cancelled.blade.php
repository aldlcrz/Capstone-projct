@extends('emails.layout')

@section('content')
<div class="greeting">❌ Order Cancelled</div>
<span class="badge badge-danger">Order #{{ $orderId }}</span>
<p>Hello {{ $recipientName }},</p>
<p>Order <strong>#{{ $orderId }}</strong> has been cancelled.</p>
<p>Reason / Note: <em>{{ $reason ?? 'Order cancelled by system or user request.' }}</em></p>

<div class="button-wrapper">
    <a href="{{ url($actionUrl ?? '/orders') }}" class="btn-primary">View Details</a>
</div>
@endsection
