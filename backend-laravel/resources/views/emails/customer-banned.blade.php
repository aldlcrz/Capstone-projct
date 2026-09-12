@extends('emails.layout')

@section('content')
<div class="greeting">Notice: Account Status Update</div>
<span class="badge badge-danger">⚠️ Account Suspended</span>
<p>Hello <strong>{{ $customerName }}</strong>,</p>
<p>This is a formal notification that your LumBarong customer account has been suspended due to platform policy violations.</p>

<div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 14px 16px; border-radius: 8px; margin: 18px 0;">
    <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; color: #991b1b; letter-spacing: 0.5px; margin-bottom: 4px;">Reason:</div>
    <div style="font-size: 13px; color: #7f1d1d; font-weight: 500;">{{ $reason }}</div>
</div>

<p style="font-size: 13px; color: #475569;">If you believe this action was taken in error or wish to appeal this decision, you may contact the LumBarong support team.</p>
@endsection
