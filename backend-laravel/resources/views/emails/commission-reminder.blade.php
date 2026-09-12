@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $sellerName }}!</div>
<span class="badge badge-warning">Commission Statement</span>
<p>This is a reminder regarding outstanding commission balance for your artisan workshop <strong>{{ $shopName }}</strong>.</p>
<div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 12px; padding: 16px; margin: 16px 0;">
    <div style="font-size: 11px; font-weight: 800; color: #92400e; text-transform: uppercase;">Total Outstanding:</div>
    <div style="font-size: 20px; font-weight: 900; color: #78350f;">₱{{ number_format($unpaidAmount, 2) }}</div>
</div>
<p style="font-size: 13px; color: #475569;">Please remit the commission via the platform to maintain good standing and uninterrupted listing access.</p>
@endsection
