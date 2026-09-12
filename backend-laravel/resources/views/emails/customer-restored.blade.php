@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $customerName }}! 🎉</div>
<span class="badge badge-success">✓ Account Restored</span>
<p>Your LumBarong customer account has been restored to <strong>Active</strong> status by the platform administration team.</p>
<p>You may now sign in to browse artisan collections, manage your orders, and enjoy shopping handcrafted Barongs.</p>
<p style="font-size: 12px; color: #71717a; margin-top: 24px;">Thank you for your patience and for being part of LumBarong.</p>
@endsection
