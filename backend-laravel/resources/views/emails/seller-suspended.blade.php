@extends('emails.layout')

@section('content')
<div class="greeting">Notice: Workshop Status Update</div>
<span class="badge badge-danger">⚠️ Account Suspended</span>
<p>Hello <strong>{{ $userName }}</strong>,</p>
<p>This is a formal notification that your artisan workshop <strong>{{ $shopName }}</strong> has been temporarily suspended from the LumBarong platform.</p>

<div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 14px 16px; border-radius: 8px; margin: 18px 0;">
    <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; color: #991b1b; letter-spacing: 0.5px; margin-bottom: 4px;">Reason for Suspension:</div>
    <div style="font-size: 13px; color: #7f1d1d; font-weight: 500;">{{ $reason }}</div>
</div>

<p>While suspended, your products will be hidden from public search, and new customer purchases and payment modifications are restricted.</p>
<p style="font-size: 12px; color: #64748b;">If you believe this action was taken in error or would like to submit clarification, you may contact the LumBarong support team.</p>
@endsection
