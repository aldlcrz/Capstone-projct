@extends('emails.layout')

@section('content')
<div class="greeting">🎉 Good News! Your Saved Item is Back in Stock</div>
<span class="badge badge-success">Restocked & Added to Cart</span>

<p>Hello <strong>{{ $customerName }}</strong>,</p>
<p>An item from your wishlist, <strong>"{{ $productName }}"</strong> by <em>{{ $shopName }}</em>, has just been restocked by the artisan!</p>

<div style="background-color: #fefce8; border: 1px solid #fef08a; padding: 20px; border-radius: 12px; margin: 20px 0;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            @if(!empty($imageUrl))
            <td style="width: 80px; vertical-align: top; padding-right: 16px;">
                <img src="{{ $imageUrl }}" alt="{{ $productName }}" style="width: 80px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid #e2e8f0;">
            </td>
            @endif
            <td style="vertical-align: top;">
                <p style="margin: 0; font-weight: 700; color: #0f172a; font-size: 16px;">{{ $productName }}</p>
                <p style="margin: 4px 0; color: #64748b; font-size: 13px;">Crafted by <strong>{{ $shopName }}</strong></p>
                @if($size)
                <p style="margin: 4px 0; font-size: 13px; color: #0f172a;">
                    Selected Size: <span style="display: inline-block; background: #3D2B1F; color: #ffffff; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 11px;">Size {{ $size }}</span>
                </p>
                @endif
                <p style="margin: 8px 0 0 0; font-size: 18px; color: #C0420A; font-weight: 800;">
                    ₱{{ number_format($price, 2) }}
                </p>
            </td>
        </tr>
    </table>
</div>

<p style="color: #475569; font-size: 14px; line-height: 1.5;">
    To ensure you don't miss out before inventory sells out again, we have <strong>automatically added 1 unit of your selected size to your shopping cart</strong>.
</p>

<div class="button-wrapper" style="margin-top: 24px;">
    <a href="{{ url('/cart') }}" class="btn-primary" style="display: inline-block; background-color: #8B0000; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: 700;">View Cart & Checkout Now</a>
</div>

<p style="margin-top: 20px; font-size: 12px; color: #94a3b8;">
    Need adjustments? You can modify quantities or remove items anytime in your <a href="{{ url('/cart') }}" style="color: #C0420A;">Shopping Cart</a>.
</p>
@endsection
