@extends('emails.layout')

@section('content')
<div class="greeting">Hello, {{ $userName }}!</div>
<span class="badge badge-info">Return / Refund Status Update</span>
<p>Your return/refund request for Order <strong>#{{ $orderId }}</strong> has been updated to: <strong style="text-transform: uppercase; color: #C0420A;">{{ $status }}</strong>.</p>
@if(!empty($reason))
<p><strong>Artisan / Admin Notes:</strong> {{ $reason }}</p>
@endif
<p style="margin-top: 14px; font-size: 13px; color: #475569;">You can view full details in your LumBarong purchases section.</p>
@endsection
