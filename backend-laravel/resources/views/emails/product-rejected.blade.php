@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $sellerName }}!</div>
<span class="badge badge-warning">⚠️ Product Listing Update</span>
<p>Your product listing <strong>{{ $productName }}</strong> was not approved for publication.</p>

<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 14px 16px; border-radius: 8px; margin: 18px 0;">
    <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; color: #92400e; letter-spacing: 0.5px; margin-bottom: 4px;">Reason for Rejection:</div>
    <div style="font-size: 13px; color: #78350f; font-weight: 500;">{{ $reason }}</div>
</div>

<p style="font-size: 13px; color: #475569;">Please log in to your Seller Control Panel to edit your product listing details, photos, or descriptions according to the feedback, and resubmit for approval.</p>
@endsection
