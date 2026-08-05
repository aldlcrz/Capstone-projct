@extends('emails.layout')

@section('content')
<div class="greeting">🏷️ Special Promotion / Price Drop!</div>
<span class="badge badge-warning">{{ $discountPercentage }}% OFF</span>
<p>Hello {{ $customerName }},</p>
<p>Good news! <strong>"{{ $productName }}"</strong> by <em>{{ $shopName }}</em> is now on sale with a <strong>{{ $discountPercentage }}% discount</strong>!</p>

<div style="background-color: #fefce8; border: 1px solid #fef08a; padding: 16px; border-radius: 12px; margin: 20px 0;">
    <p style="margin: 0; font-weight: 700; color: #0f172a; font-size: 16px;">{{ $productName }}</p>
    <p style="margin: 4px 0 8px 0; color: #64748b; font-size: 13px;">By {{ $shopName }}</p>
    <p style="margin: 0; font-size: 18px;">
        <span style="text-decoration: line-through; color: #94a3b8; font-size: 14px;">₱{{ number_format($originalPrice, 2) }}</span>
        <strong style="color: #C0420A; font-weight: 800; margin-left: 8px;">₱{{ number_format($salePrice, 2) }}</strong>
    </p>
</div>

<div class="button-wrapper">
    <a href="{{ url('/products/' . $productId) }}" class="btn-primary">Shop Discounted Product</a>
</div>
@endsection
