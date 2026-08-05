@extends('emails.layout')

@section('content')
<div class="greeting">⚠️ New Return / Refund Request</div>
<span class="badge badge-warning">Order #{{ $orderId }}</span>
<p>Hello {{ $sellerName }},</p>
<p>A customer has submitted a {{ $requestType ?? 'Return/Refund' }} request for Order <strong>#{{ $orderId }}</strong>.</p>
<p><strong>Reason provided:</strong> {{ $reason }}</p>

<div class="button-wrapper">
    <a href="{{ url('/seller/orders') }}" class="btn-primary">Review Request</a>
</div>
@endsection
