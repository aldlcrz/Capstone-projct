@extends('emails.layout')

@section('content')
<div class="greeting">Product Listing Notice</div>
<span class="badge badge-danger">Listing Removed</span>
<p>Hello {{ $sellerName }},</p>
<p>This is to inform you that your product listing <strong>"{{ $productName }}"</strong> has been permanently removed from the LumBarong marketplace by a platform administrator.</p>

<div style="background-color: #f4f4f5; border-left: 4px solid #71717a; padding: 16px; border-radius: 8px; margin: 20px 0;">
    <p style="margin: 0 0 6px 0; font-weight: 700; color: #27272a; font-size: 13px; text-transform: uppercase;">Recorded Deletion Reason:</p>
    <p style="margin: 0; color: #3f3f46; font-weight: 600; font-size: 14px;">{{ $reason }}</p>
</div>

<p>This product is no longer active in your artisan catalog or visible to customers. If you have any inquiries, please contact our support team at <a href="mailto:lumbarongsupport@gmail.com" style="color: #c0420a; font-weight: bold; text-decoration: underline;">lumbarongsupport@gmail.com</a>.</p>

<div class="button-wrapper">
    <a href="mailto:lumbarongsupport@gmail.com?subject=Product%20Deletion%20Inquiry%20-%20{{ urlencode($productName) }}" class="btn-primary">Email Support (lumbarongsupport@gmail.com)</a>
</div>
@endsection
