@extends('emails.layout')

@section('content')
<div class="greeting">Mabuhay, {{ $sellerName }}! 🎉</div>
<span class="badge badge-success">✓ Product Approved</span>
<p>Your product listing <strong>{{ $productName }}</strong> has been reviewed and approved by the LumBarong Administration.</p>
<p>It is now live in the marketplace catalog for customers to discover and purchase.</p>
@endsection
