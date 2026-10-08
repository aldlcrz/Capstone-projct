<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Pickup Receipt #LB-OR-{{ strtoupper(substr($order->id, -8)) }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', 'Helvetica', 'Arial', sans-serif;
        }
        body {
            background-color: #FFFFFF;
            color: #1E1915;
            padding: 28px 32px;
            font-size: 11px;
            line-height: 1.4;
        }
        .header-table {
            width: 100%;
            border-bottom: 2px solid #C49520;
            padding-bottom: 14px;
            margin-bottom: 16px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: bold;
            color: #1E1915;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }
        .brand-sub {
            font-size: 9px;
            color: #A87B10;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 11px;
            font-weight: bold;
            color: #1E1915;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-top: 4px;
        }
        .receipt-badge {
            text-align: right;
            vertical-align: middle;
        }
        .badge-box {
            display: inline-block;
            background-color: #FAF5EA;
            border: 1.5px solid #E6D8BA;
            color: #8C6212;
            padding: 8px 14px;
            border-radius: 8px;
            text-align: right;
        }
        .badge-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #78716C;
        }
        .badge-code {
            font-size: 15px;
            font-weight: bold;
            color: #C0420A;
            letter-spacing: 1.5px;
            margin-top: 2px;
        }

        /* Highlight Grid */
        .highlight-table {
            width: 100%;
            background-color: #FDFBF7;
            border: 1px solid #EAE2D2;
            border-radius: 8px;
            margin-bottom: 16px;
            padding: 12px 14px;
        }
        .info-col {
            width: 50%;
            vertical-align: top;
            padding: 0 8px;
        }
        .section-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #8C827A;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #EAE1D0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .info-row {
            margin-bottom: 4px;
            font-size: 10px;
            color: #374151;
        }
        .info-row strong {
            color: #1E1915;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .items-table th {
            background-color: #1E1915;
            color: #DFC97A;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }
        .items-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #ECE3D2;
            font-size: 10px;
        }
        .items-table tr:nth-child(even) {
            background-color: #FAF8F5;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }

        /* Summary & Handover Section */
        .summary-table {
            width: 100%;
            margin-bottom: 16px;
        }
        .summary-left {
            width: 52%;
            vertical-align: top;
            padding-right: 14px;
        }
        .summary-right {
            width: 48%;
            vertical-align: top;
        }

        /* Handover Box */
        .handover-box {
            background-color: #FAF8F5;
            border: 1px solid #ECE3D2;
            border-radius: 8px;
            padding: 10px 12px;
        }
        .handover-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #8C827A;
            border-bottom: 1px solid #EAE1D0;
            padding-bottom: 3px;
            margin-bottom: 8px;
        }
        .handover-note {
            font-size: 9px;
            color: #5C5247;
            line-height: 1.35;
            margin-bottom: 10px;
        }
        .sig-row {
            width: 100%;
            margin-top: 6px;
        }
        .sig-col {
            width: 50%;
            vertical-align: bottom;
            padding-right: 6px;
        }
        .sig-line {
            border-bottom: 1px solid #C4B5A5;
            height: 18px;
            margin-bottom: 3px;
        }
        .sig-label {
            font-size: 8px;
            color: #78716C;
            text-transform: uppercase;
            font-weight: bold;
        }

        /* Totals Box */
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #FAF8F5;
            border: 1px solid #ECE3D2;
            border-radius: 8px;
            overflow: hidden;
        }
        .totals-table td {
            padding: 7px 12px;
            font-size: 10px;
        }
        .grand-total {
            background-color: #1E1915;
            color: #DFC97A;
            font-weight: bold;
            font-size: 12px;
        }
        .grand-total td {
            padding: 9px 12px;
        }

        .auth-badge {
            display: inline-block;
            background-color: #ECFDF5;
            border: 1px solid #A7F3D0;
            color: #047857;
            font-weight: bold;
            font-size: 8px;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 6px;
        }

        .footer {
            border-top: 1px dashed #D6CEBE;
            padding-top: 10px;
            text-align: center;
            font-size: 8px;
            color: #8C827A;
            margin-top: 14px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    {{-- Header Section --}}
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <div class="brand-title">LumBarong</div>
                <div class="brand-sub">Lumban Heritage Craft &amp; Artisan Marketplace</div>
                <div class="doc-title">
                    Store Pickup Collection Pass &amp; Official Receipt
                </div>
            </td>
            <td class="receipt-badge">
                <div class="badge-box">
                    <div class="badge-title">Pickup Claim Code</div>
                    <div class="badge-code">{{ $pickupCode }}</div>
                    <div style="font-size: 8px; color: #78716C; margin-top: 2px;">Order #LB-OR-{{ strtoupper(substr($order->id, -8)) }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Highlight Information Grid --}}
    <table class="highlight-table">
        <tr>
            {{-- Buyer Details --}}
            <td class="info-col">
                <div class="section-label">Buyer / Claiming Customer</div>
                <div class="info-row"><strong>Name:</strong> {{ $order->customer?->name ?? 'Customer' }}</div>
                <div class="info-row"><strong>Email:</strong> {{ $order->customer?->email ?? 'N/A' }}</div>
                <div class="info-row"><strong>Phone:</strong> {{ $order->customer?->mobileNumber ?? ($order->normalized_shipping_address['phone'] ?? 'N/A') }}</div>
                <div class="info-row"><strong>Order Placed:</strong> {{ $order->createdAt ? $order->createdAt->format('M d, Y • g:i A') : 'N/A' }}</div>
            </td>

            {{-- Artisan Workshop Details --}}
            <td class="info-col">
                <div class="section-label">Pickup Workshop &amp; Artisan</div>
                <div class="info-row"><strong>Artisan Shop:</strong> {{ $order->seller?->shopName ?: $order->seller?->name }}</div>
                <div class="info-row"><strong>Collection Point:</strong> Lumban Workshop Studio, Lumban, Laguna</div>
                <div class="info-row"><strong>Fulfillment Method:</strong> In-Shop Store Pickup (Free / ₱0.00)</div>
                <div class="info-row"><strong>Payment Method:</strong> {{ $order->formatted_payment_method }}</div>
            </td>
        </tr>
    </table>

    {{-- Purchased Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 45%;">Item / Heritage Piece</th>
                <th style="width: 15%;">Variant / Size</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 15%;" class="text-right">Unit Price</th>
                <th style="width: 15%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php
                $itemsSubtotal = 0;
            @endphp
            @foreach($order->items as $item)
                @php
                    $lineTotal = $item->price * $item->quantity;
                    $itemsSubtotal += $lineTotal;
                    $variationLabel = $item->display_variation ?? $item->variation;
                    $itemTitle = (!empty($variationLabel) && strcasecmp($variationLabel, 'Original') !== 0) ? $variationLabel : ($item->product?->name ?? $item->product_name ?? 'Heritage Piece');
                @endphp
                <tr>
                    <td>
                        <strong>{{ $itemTitle }}</strong>
                        @if($item->product && $item->product->name && $item->product->name !== $itemTitle)
                            <div style="font-size: 8px; color: #8C827A;">{{ $item->product->name }}</div>
                        @endif
                    </td>
                    <td>
                        {{ $item->size ? 'Size: ' . $item->size : 'Standard' }}
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">₱{{ number_format($item->price, 2) }}</td>
                    <td class="text-right">₱{{ number_format($lineTotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Summary & Handover Section --}}
    <table class="summary-table">
        <tr>
            {{-- Handover & Workshop Release Block --}}
            <td class="summary-left">
                <div class="handover-box">
                    <div class="handover-title">Workshop Release &amp; Claim Acknowledgment</div>
                    <div class="handover-note">
                        This official document authorizes the release of the handcrafted piece(s) listed above upon presentation at the Lumban workshop.
                    </div>
                    
                    <table class="sig-row">
                        <tr>
                            <td class="sig-col">
                                <div class="sig-line"></div>
                                <div class="sig-label">Customer / Claimant Signature</div>
                            </td>
                            <td class="sig-col">
                                <div class="sig-line"></div>
                                <div class="sig-label">Artisan Workshop Release</div>
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top: 6px;">
                        <span class="auth-badge">✓ Verified LumBarong Artisan Pass</span>
                    </div>
                </div>
            </td>

            {{-- Totals Summary --}}
            <td class="summary-right">
                <table class="totals-table">
                    <tr>
                        <td style="color: #8C827A;">Items Subtotal:</td>
                        <td class="text-right font-bold">₱{{ number_format($itemsSubtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="color: #8C827A;">Fulfillment (Store Pickup):</td>
                        <td class="text-right font-bold" style="color: #059669;">FREE (₱0.00)</td>
                    </tr>
                    @if(($order->totalAmount - $itemsSubtotal) != 0 && ($order->totalAmount - $itemsSubtotal) < 0)
                    <tr>
                        <td style="color: #8C827A;">Discount Applied:</td>
                        <td class="text-right font-bold" style="color: #C0420A;">-₱{{ number_format(abs($order->totalAmount - $itemsSubtotal), 2) }}</td>
                    </tr>
                    @endif
                    <tr class="grand-total">
                        <td>FINAL TOTAL AMOUNT:</td>
                        <td class="text-right">₱{{ number_format($order->totalAmount, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Footer Stamp --}}
    <div class="footer">
        Generated on {{ $generatedAt->format('F d, Y \a\t g:i A') }} • LumBarong Digital Fulfillment System • Official Store Pickup Pass<br>
        This document serves as proof of order readiness and pickup authorization for Order #{{ $order->id }}.
    </div>

</body>
</html>
