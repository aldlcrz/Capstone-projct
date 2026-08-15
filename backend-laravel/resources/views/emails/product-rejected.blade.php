@extends('emails.layout')

@section('content')
<div class="greeting">⚠️ Product Submission Update</div>
<span class="badge badge-danger">Needs Revision</span>
<p>Hello {{ $sellerName }},</p>
<p>Your product listing submission for <strong>"{{ $productName }}"</strong> was reviewed and requires corrections before it can be published.</p>

<div style="background-color: #fff1f2; border-left: 4px solid #e11d48; padding: 16px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0 0 6px 0; font-weight: 700; color: #9f1239; font-size: 13px; text-transform: uppercase;">Specific Reason for Rejection:</p>
    <p style="margin: 0; color: #881337; font-weight: 600;">{{ $rejectionReason ?? 'Listing does not adhere to LumBarong quality guidelines.' }}</p>
</div>

<p>Please log in to your Seller Center to revise and resubmit your product for approval.</p>

<div class="button-wrapper">
    <a href="{{ url('/seller/products/' . $productId . '/edit') }}" class="btn-primary">Edit and Resubmit Product</a>
</div>
@endsection
