@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName }}!</div>
<span class="badge badge-danger">Order Cancelled</span>
<p>Your order <strong>#{{ $orderId }}</strong> has been cancelled.</p>
@if(!empty($reason))
<p><strong>Reason:</strong> {{ $reason }}</p>
@endif
<p style="margin-top: 14px; font-size: 13px; color: #475569;">If you believe this cancellation was made in error, please contact the artisan shop directly.</p>
@endsection
