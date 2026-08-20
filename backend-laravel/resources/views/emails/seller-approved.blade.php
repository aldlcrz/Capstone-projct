@extends('emails.layout')

@section('content')
<div class="greeting">🎉 Welcome to LumBarong Artisans!</div>
<span class="badge badge-success">Shop Verified &amp; Active</span>
<p>Hello {{ $sellerName }},</p>
<p>Great news! Your artisan shop registration for <strong>"{{ $shopName }}"</strong> has been officially reviewed and approved by the LumBarong administration team.</p>
<p>Your seller profile and storefront are now active. You can now access your Seller Dashboard, upload barong pieces and crafts, manage incoming orders, and customize your shop policies.</p>

<div class="button-wrapper">
    <a href="{{ url('/seller/dashboard') }}" class="btn-primary">Go to Seller Dashboard</a>
</div>

<p style="font-size: 12px; color: #71717a; margin-top: 24px;">If you have any questions or need assistance setting up your products, feel free to email our artisan support team at <a href="mailto:lumbarongsupport@gmail.com" style="color: #c0420a; font-weight: bold; text-decoration: underline;">lumbarongsupport@gmail.com</a>.</p>
@endsection
