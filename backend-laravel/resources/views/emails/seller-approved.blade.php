@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $userName }}! 🎉</div>
<span class="badge badge-success">✓ Application Approved &amp; Verified</span>
<p>Congratulations! Your artisan workshop <strong>{{ $shopName }}</strong> has been verified and approved by the LumBarong Administration team.</p>
<p>Your shop is now active on the marketplace. You can now manage artisan product listings, set up custom embroidery options, and fulfill customer orders through your Seller Control Panel.</p>
<p style="font-size: 12px; color: #71717a; margin-top: 24px;">Welcome to the LumBarong artisan community representing Lumban, Laguna!</p>
@endsection
