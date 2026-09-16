@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $sellerName }}!</div>
<span class="badge {{ $badgeClass ?? 'badge-warning' }}">{{ $reminderTitle ?? 'Commission Statement' }}</span>
<p>This is a notification regarding the monthly commission remittance for your artisan workshop <strong>{{ $shopName ?? $sellerName }}</strong> (Period: <strong>{{ $period ?? 'Current Cycle' }}</strong>).</p>

@if(!empty($reminderMessage))
<p style="font-size: 13px; color: #334155; margin: 10px 0;">{{ $reminderMessage }}</p>
@endif

<div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 12px; padding: 16px; margin: 16px 0;">
    <div style="font-size: 11px; font-weight: 800; color: #92400e; text-transform: uppercase;">Total Outstanding Amount:</div>
    <div style="font-size: 20px; font-weight: 900; color: #78350f;">₱{{ number_format($amountDue ?? ($unpaidAmount ?? 0), 2) }}</div>
    @if(!empty($dueDateFormatted))
    <div style="font-size: 11px; color: #92400e; margin-top: 4px;">Due Date: <strong>{{ $dueDateFormatted }}</strong></div>
    @endif
</div>
<p style="font-size: 13px; color: #475569;">Please remit your monthly fee and upload proof of payment via the Artisan Control Panel to ensure uninterrupted storefront operations.</p>
@endsection
