@extends('emails.layout')

@section('content')
<div class="greeting">🎉 Artisan Account Restored</div>
<span class="badge badge-success">Account Active</span>
<p>Hello {{ $sellerName }},</p>
<p>Good news! Your artisan account and shop <strong>"{{ $shopName }}"</strong> have been reinstated and restored to active status by the LumBarong administration team.</p>
<p>Your shop profile and product listings are once again active in the marketplace, and your seller tools are fully accessible.</p>

<div class="button-wrapper">
    <a href="{{ url('/seller/dashboard') }}" class="btn-primary">Go to Seller Dashboard</a>
</div>
@endsection
