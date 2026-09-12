@extends('emails.layout')

@section('content')
<div class="greeting">Notice: Artisan Application Status</div>

@if(($rejectionType ?? 'document_correction') === 'ineligible')
    <span class="badge badge-danger" style="background:#fee2e2; color:#991b1b; padding:4px 10px; border-radius:9999px; font-weight:bold; font-size:11px; text-transform:uppercase;">🚫 Application Ineligible</span>
@else
    <span class="badge badge-warning" style="background:#fef3c7; color:#92400e; padding:4px 10px; border-radius:9999px; font-weight:bold; font-size:11px; text-transform:uppercase;">⚠️ Document Correction Required</span>
@endif

<p>Hello <strong>{{ $userName ?? $sellerName }}</strong>,</p>

@if(($rejectionType ?? 'document_correction') === 'ineligible')
    <p>Thank you for your interest in joining LumBarong for <strong>{{ $shopName }}</strong>. After administrative review, we regret to inform you that your application does not meet LumBarong's eligibility requirements for local Lumban artisans.</p>
@else
    <p>Thank you for applying to LumBarong as an artisan partner for <strong>{{ $shopName }}</strong>. To proceed with your account approval, we require updates or corrections to your submitted credentials.</p>
@endif

@if(!empty($reason))
<div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 14px 16px; border-radius: 8px; margin: 18px 0;">
    <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; color: #92400e; letter-spacing: 0.5px; margin-bottom: 4px;">Reason / Notes:</div>
    <div style="font-size: 13px; color: #78350f; font-weight: 500;">{{ $reason }}</div>
</div>
@endif

@if(($rejectionType ?? 'document_correction') === 'ineligible')
    <p style="color: #6b7280; font-size: 12px; line-height: 1.5;">LumBarong is exclusively designated for verified artisans and heritage workshops located in Lumban, Laguna. If you believe this assessment was made in error, please contact our support team at <a href="mailto:lumbarongsupport@gmail.com" style="color:#C0422A; font-weight:bold;">lumbarongsupport@gmail.com</a>.</p>
@else
    <p style="color: #374151; font-size: 13px; line-height: 1.5;">You can log in to your LumBarong account to access the <strong>Document Re-upload Portal</strong> and attach your updated files for immediate re-evaluation.</p>
@endif
@endsection
