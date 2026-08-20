@extends('emails.layout')

@section('content')
<div class="greeting">Account Deletion Notice</div>
<span class="badge badge-danger">Account Removed</span>
<p>Hello {{ $customerName }},</p>
<p>This is to confirm that your customer account on LumBarong has been permanently removed by an administrator.</p>

<div style="background-color: #f4f4f5; border-left: 4px solid #71717a; padding: 16px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0 0 6px 0; font-weight: 700; color: #27272a; font-size: 13px; text-transform: uppercase;">Recorded Reason:</p>
    <p style="margin: 0; color: #3f3f46; font-weight: 600; font-size: 14px;">{{ $reason }}</p>
</div>

<p>Your profile and personal records have been permanently cleared from our system. If you have questions regarding this deletion, you may reach out to our platform support desk at <a href="mailto:lumbarongsupport@gmail.com" style="color: #c0420a; font-weight: bold; text-decoration: underline;">lumbarongsupport@gmail.com</a>.</p>

<div class="button-wrapper">
    <a href="mailto:lumbarongsupport@gmail.com?subject=Account%20Deletion%20Inquiry%20-%20{{ urlencode($customerName) }}" class="btn-primary">Email Support (lumbarongsupport@gmail.com)</a>
</div>
@endsection
