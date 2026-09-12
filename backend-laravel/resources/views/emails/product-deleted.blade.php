@extends('emails.layout')

@section('content')
<div class="greeting">Notice: Product Listing Removed</div>
<span class="badge badge-danger">Product Archived</span>
<p>Hello <strong>{{ $sellerName }}</strong>,</p>
<p>Your product listing <strong>{{ $productName }}</strong> has been removed and archived from your shop catalog.</p>

<div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 14px 16px; border-radius: 8px; margin: 18px 0;">
    <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; color: #991b1b; letter-spacing: 0.5px; margin-bottom: 4px;">Reason:</div>
    <div style="font-size: 13px; color: #7f1d1d; font-weight: 500;">{{ $reason }}</div>
</div>

<p style="font-size: 13px; color: #475569;">This product is no longer active in your catalog or visible to customers on the marketplace.</p>
@endsection
