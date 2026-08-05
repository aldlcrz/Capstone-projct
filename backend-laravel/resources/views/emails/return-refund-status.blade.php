@extends('emails.layout')

@section('content')
<div class="greeting">Update on Your Return / Refund Request</div>
<span class="badge badge-info">{{ $status }}</span>
<p>Hello {{ $customerName }},</p>
<p>Your {{ $requestType ?? 'Return/Refund' }} request for Order <strong>#{{ $orderId }}</strong> has been updated to: <strong>{{ $status }}</strong>.</p>
@if(!empty($comments))
<p><strong>Seller / Admin Comments:</strong> <em>{{ $comments }}</em></p>
@endif

<div class="button-wrapper">
    <a href="{{ url('/orders/' . $orderId) }}" class="btn-primary">View Request Details</a>
</div>
@endsection
