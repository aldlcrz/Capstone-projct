@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $sellerName }}!</div>
<span class="badge badge-warning">Return / Refund Request</span>
<p>A customer has filed a return/refund request for Order <strong>#{{ $orderId }}</strong>.</p>
<p><strong>Reason:</strong> {{ $reason }}</p>
<p style="margin-top: 14px; font-size: 13px; color: #475569;">Please log in to your Seller Control Panel to inspect the return details and provide an artisan resolution.</p>
@endsection
