@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $userName }}! 🎉</div>
<span class="badge badge-success">✓ Account Restored</span>
<p>Great news! Your artisan workshop <strong>{{ $shopName }}</strong> has been reinstated and restored to <strong>Active</strong> status by the LumBarong Administration.</p>
<p>Your shop profile, product catalog, and seller operations are now fully accessible and visible to customers across the marketplace.</p>
<p style="font-size: 12px; color: #71717a; margin-top: 24px;">Thank you for your cooperation and dedication to authentic Lumban craftsmanship.</p>
@endsection
