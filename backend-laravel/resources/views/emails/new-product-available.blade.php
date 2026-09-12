@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $userName }}! ✨</div>
<span class="badge badge-info">New Artisan Arrival</span>
<p>A new handcrafted product has just arrived from an artisan workshop you follow: <strong>{{ $shopName }}</strong>.</p>

<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin: 16px 0;">
    <div style="font-weight: 800; font-size: 15px; color: #1e293b;">{{ $productName }}</div>
    <div style="font-weight: 700; font-size: 14px; color: #C0420A; margin-top: 4px;">₱{{ number_format($price, 2) }}</div>
</div>

<p style="font-size: 13px; color: #475569;">Explore the latest collections from Lumban, Laguna on LumBarong.</p>
@endsection
