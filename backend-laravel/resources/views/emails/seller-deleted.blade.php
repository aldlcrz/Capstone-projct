@extends('emails.layout')

@section('content')
<div class="greeting">Artisan Account Notice</div>
<span class="badge badge-danger">Account Removed</span>
<p>Hello {{ $sellerName }},</p>
<p>This is to inform you that your seller account and artisan workshop <strong>"{{ $shopName }}"</strong> have been permanently removed from the LumBarong platform by an administrator.</p>

<div style="background-color: #f4f4f5; border-left: 4px solid #71717a; padding: 16px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0 0 6px 0; font-weight: 700; color: #27272a; font-size: 13px; text-transform: uppercase;">Recorded Reason:</p>
    <p style="margin: 0; color: #3f3f46; font-weight: 600; font-size: 14px;">{{ $reason }}</p>
</div>

<p>All active listings and shop profile data have been purged from the platform registry. If you have questions regarding this deletion, you may reach out to our platform team.</p>

<div class="button-wrapper">
    <a href="{{ url('/contact') }}" class="btn-primary">Contact Platform Support</a>
</div>
@endsection
