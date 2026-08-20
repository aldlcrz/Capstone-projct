@extends('emails.layout')

@section('content')
<div class="greeting">⚠️ Account Status Notification</div>
<span class="badge badge-danger">Account Suspended</span>
<p>Hello {{ $customerName }},</p>
<p>We are writing to notify you that your LumBarong customer account has been suspended by our platform administrators.</p>

<div style="background-color: #fff1f2; border-left: 4px solid #e11d48; padding: 16px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0 0 6px 0; font-weight: 700; color: #9f1239; font-size: 13px; text-transform: uppercase;">Reason for Suspension:</p>
    <p style="margin: 0; color: #881337; font-weight: 600; font-size: 14px;">{{ $reason }}</p>
</div>

<p>While your account is suspended, you will not be able to log in, place new orders, or participate in the marketplace.</p>
<p>If you believe this action was taken in error or wish to appeal this decision, please contact our support team at <a href="mailto:lumbarongsupport@gmail.com" style="color: #c0420a; font-weight: bold; text-decoration: underline;">lumbarongsupport@gmail.com</a>.</p>

<div class="button-wrapper">
    <a href="mailto:lumbarongsupport@gmail.com?subject=Customer%20Account%20Suspension%20Appeal%20-%20{{ urlencode($customerName) }}" class="btn-primary">Email Support (lumbarongsupport@gmail.com)</a>
</div>
@endsection
