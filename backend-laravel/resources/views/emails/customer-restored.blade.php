@extends('emails.layout')

@section('content')
<div class="greeting">🎉 Welcome Back!</div>
<span class="badge badge-success">Account Restored</span>
<p>Hello {{ $customerName }},</p>
<p>We are pleased to inform you that your LumBarong customer account has been reviewed and reinstated to active status by our administration team.</p>
<p>You can now log in and continue browsing, purchasing, and interacting with artisan workshops across the marketplace.</p>

<div class="button-wrapper">
    <a href="{{ url('/login') }}" class="btn-primary">Log In to LumBarong</a>
</div>

<p style="font-size: 12px; color: #71717a; margin-top: 24px;">If you have any questions or need further support, please email us at <a href="mailto:lumbarongsupport@gmail.com" style="color: #c0420a; font-weight: bold; text-decoration: underline;">lumbarongsupport@gmail.com</a>.</p>
@endsection
