@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $customerName ?? ($userName ?? 'Valued Customer') }}!</div>
<span class="badge badge-info">Return / Refund Update</span>
<p>Your return/refund request for Order <strong>#{{ $orderId }}</strong> has been updated to: <strong style="text-transform: uppercase; color: #C0420A;">{{ $status }}</strong>.</p>
@if(!empty($comments ?? $reason))
<p><strong>Artisan / Admin Notes:</strong> {{ $comments ?? $reason }}</p>
@endif
<p style="margin-top: 14px; font-size: 13px; color: #475569;">You may check your customer order details on the LumBarong website for further updates.</p>
@endsection
