@extends('emails.layout')

@section('content')
<div class="greeting">Notice: Artisan Application Status</div>
<span class="badge badge-warning">⚠️ Application Declined</span>
<p>Hello <strong>{{ $userName }}</strong>,</p>
<p>Thank you for your interest in joining LumBarong as an artisan seller for <strong>{{ $shopName }}</strong>. After careful administrative review, your application was not approved at this time.</p>

@if(!empty($reason))
<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 14px 16px; border-radius: 8px; margin: 18px 0;">
    <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; color: #92400e; letter-spacing: 0.5px; margin-bottom: 4px;">Reason for Rejection:</div>
    <div style="font-size: 13px; color: #78350f; font-weight: 500;">{{ $reason }}</div>
</div>
@endif

<p>You may update your business documentation or address information and submit a new request once the requirements are met.</p>
@endsection
