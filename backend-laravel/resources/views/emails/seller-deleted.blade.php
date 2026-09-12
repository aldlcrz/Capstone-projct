@extends('emails.layout')

@section('content')
<div class="greeting">Notice: Artisan Account Deletion</div>
<span class="badge badge-danger">🛑 Account Closed</span>
<p>Hello <strong>{{ $userName }}</strong>,</p>
<p>This is a formal notification that your artisan seller account for <strong>{{ $shopName }}</strong> has been deleted and archived from the LumBarong platform.</p>

<div style="background: #fee2e2; border-left: 4px solid #ef4444; padding: 14px 16px; border-radius: 8px; margin: 18px 0;">
    <div style="font-weight: 800; font-size: 11px; text-transform: uppercase; color: #991b1b; letter-spacing: 0.5px; margin-bottom: 4px;">Deletion Note / Reason:</div>
    <div style="font-size: 13px; color: #7f1d1d; font-weight: 500;">{{ $reason }}</div>
</div>

<p>Your shop listings have been safely archived. Your email address remains available should you wish to register again in the future.</p>
@endsection
