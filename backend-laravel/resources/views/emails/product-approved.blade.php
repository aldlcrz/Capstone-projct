@extends('emails.layout')

@section('content')
<div class="greeting">🎉 Product Approved!</div>
<span class="badge badge-success">Live Listing</span>
<p>Hello {{ $sellerName }},</p>
<p>Great news! Your product listing <strong>"{{ $productName }}"</strong> has been reviewed and approved by the LumBarong moderation team.</p>
<p>Your product is now available for purchase in the marketplace catalog.</p>

<div class="button-wrapper">
    <a href="{{ url('/products/' . $productId) }}" class="btn-primary">View Product Listing</a>
</div>
@endsection
