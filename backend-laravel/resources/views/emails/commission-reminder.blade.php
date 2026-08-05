@extends('emails.layout')

@section('content')
<div class="greeting">💼 {{ $reminderTitle }}</div>
<span class="badge {{ $badgeClass }}">{{ $reminderType }}</span>
<p>Hello {{ $sellerName }},</p>
<p>{{ $reminderMessage }}</p>

<div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 12px; margin: 20px 0;">
    <p style="margin: 0 0 6px 0; color: #64748b; font-size: 12px; font-weight: 700; text-transform: uppercase;">Commission Summary</p>
    <p style="margin: 0 0 4px 0; font-size: 14px; color: #0f172a;">Billing Period: <strong>{{ $period }}</strong></p>
    <p style="margin: 0 0 4px 0; font-size: 14px; color: #0f172a;">Total Amount Due: <strong style="color: #C0420A;">₱{{ number_format($amountDue, 2) }}</strong></p>
    <p style="margin: 0; font-size: 14px; color: #0f172a;">Due Date: <strong>{{ $dueDateFormatted }}</strong></p>
</div>

<p>To avoid shop freeze or service disruption, please submit your payment proof promptly in the Seller Center.</p>

<div class="button-wrapper">
    <a href="{{ url('/seller/commission') }}" class="btn-primary">Pay Commission Now</a>
</div>
@endsection
