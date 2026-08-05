@extends('emails.layout')

@section('content')
<div class="greeting">✨ New Heritage Product Now Available!</div>
<span class="badge badge-info">Fresh Arrival</span>
<p>Hello {{ $customerName }},</p>
<p>An exquisite new artisan piece, <strong>"{{ $productName }}"</strong> by <em>{{ $shopName }}</em>, is now available on LumBarong!</p>

<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 12px; margin: 20px 0;">
    <p style="margin: 0; font-weight: 700; color: #0f172a; font-size: 16px;">{{ $productName }}</p>
    <p style="margin: 4px 0 8px 0; color: #64748b; font-size: 13px;">Crafted by {{ $shopName }}</p>
    <p style="margin: 0; font-weight: 800; color: #C0420A; font-size: 18px;">₱{{ number_format($price, 2) }}</p>
</div>

<div class="button-wrapper">
    <a href="{{ url('/products/' . $productId) }}" class="btn-primary">View Product Page</a>
</div>
@endsection
