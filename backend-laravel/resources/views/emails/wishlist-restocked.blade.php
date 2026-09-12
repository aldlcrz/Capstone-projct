@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $userName }}! ✨</div>
<span class="badge badge-success">Back in Stock</span>
<p>An artisan handcrafted item on your wishlist is back in stock!</p>

<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin: 16px 0;">
    <div style="font-weight: 800; font-size: 15px; color: #1e293b;">{{ $productName }}</div>
    <div style="font-weight: 700; font-size: 14px; color: #C0420A; margin-top: 4px;">₱{{ number_format($price, 2) }}</div>
    @if(!empty($size))
        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Restocked Size: <strong>{{ $size }}</strong></div>
    @endif
</div>

<p style="font-size: 13px; color: #475569;">You can visit the LumBarong store to add this item to your cart and complete checkout.</p>
@endsection
