@extends('emails.layout')

@section('content')
<div class="greeting">⚠️ Account Status Notification</div>
<span class="badge badge-danger">Account Suspended</span>
<p>Hello {{ $sellerName }},</p>
<p>This is an official notice that your artisan account and shop <strong>"{{ $shopName }}"</strong> have been suspended by the platform administrator.</p>

<div style="background-color: #fff1f2; border-left: 4px solid #e11d48; padding: 16px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0 0 6px 0; font-weight: 700; color: #9f1239; font-size: 13px; text-transform: uppercase;">Reason for Suspension:</p>
    <p style="margin: 0; color: #881337; font-weight: 600; font-size: 14px;">{{ $reason }}</p>
</div>

<p>While your account is suspended, your product listings are hidden from the marketplace catalog, and access to seller tools is temporarily paused.</p>
<p>If you believe this was done in error or would like to submit an appeal with additional verification documents, please contact our support team.</p>

<div class="button-wrapper">
    <a href="{{ url('/contact') }}" class="btn-primary">Contact Artisan Support</a>
</div>
@endsection
