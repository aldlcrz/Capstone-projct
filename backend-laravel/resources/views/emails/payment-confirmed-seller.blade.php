@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $sellerName }}!</div>
<span class="badge badge-success">✓ Payment Confirmed</span>
<p>Payment for order <strong>#{{ $orderId }}</strong> (Amount: <strong>₱{{ number_format($totalAmount, 2) }}</strong>) has been confirmed.</p>
<p style="margin-top: 14px; font-size: 13px; color: #475569;">You can now proceed with tailoring and shipping preparation for this customer.</p>
@endsection
