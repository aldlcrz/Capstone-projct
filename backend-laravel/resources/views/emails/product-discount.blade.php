@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $userName }}! 🏷️</div>
<span class="badge badge-success">Special Price Drop</span>
<p>A handcrafted item from your favorites is now available at a special discount!</p>

<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin: 16px 0;">
    <div style="font-weight: 800; font-size: 15px; color: #1e293b;">{{ $productName }}</div>
    <div style="margin-top: 6px;">
        <span style="text-decoration: line-through; color: #94a3b8; font-size: 13px;">₱{{ number_format($oldPrice, 2) }}</span>
        <span style="font-weight: 800; font-size: 16px; color: #C0420A; margin-left: 8px;">₱{{ number_format($newPrice, 2) }}</span>
        <span style="background: #fee2e2; color: #991b1b; font-size: 11px; font-weight: 800; padding: 2px 6px; border-radius: 6px; margin-left: 6px;">{{ $discountPercentage }}% OFF</span>
    </div>
</div>

<p style="font-size: 13px; color: #475569;">Visit LumBarong today to discover artisan promotions and support local Lumban weavers.</p>
@endsection
