<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LumBarong - Account Information Export</title>
    <style>
        @page {
            margin: 28px 32px 35px 32px;
            size: a4 portrait;
        }
        * {
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }
        body {
            font-size: 11px;
            line-height: 1.45;
            color: #1E1915;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2.5px solid #C49520;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .brand-title {
            font-size: 22px;
            font-weight: bold;
            color: #1E1915;
            letter-spacing: 1px;
            margin: 0;
        }
        .brand-sub {
            font-size: 9.5px;
            color: #C49520;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 2px;
        }
        .doc-badge {
            text-align: right;
            font-size: 10px;
            color: #78716C;
        }
        .doc-badge strong {
            color: #1E1915;
            font-size: 11px;
        }
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #1E1915;
            border-bottom: 1.5px solid #EAE1D0;
            padding-bottom: 4px;
            margin-top: 18px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .info-table th, .info-table td {
            padding: 5px 8px;
            text-align: left;
            vertical-align: top;
        }
        .info-table th {
            background-color: #FAF7F0;
            color: #78716C;
            font-weight: bold;
            font-size: 9.5px;
            text-transform: uppercase;
            width: 25%;
            border: 1px solid #EAE1D0;
        }
        .info-table td {
            border: 1px solid #EAE1D0;
            font-size: 10.5px;
            color: #1E1915;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 16px;
        }
        .data-table th {
            background-color: #1E1915;
            color: #FAF7F0;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 6px 8px;
            border: 1px solid #1E1915;
            text-align: left;
        }
        .data-table td {
            padding: 6px 8px;
            border: 1px solid #EAE1D0;
            font-size: 10px;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td {
            background-color: #FCFBF7;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 8.5px;
            font-weight: bold;
            border-radius: 4px;
            text-transform: uppercase;
        }
        .badge-gold {
            background-color: #FEF3C7;
            color: #92400E;
            border: 1px solid #FCD34D;
        }
        .badge-green {
            background-color: #D1FAE5;
            color: #065F46;
            border: 1px solid #A7F3D0;
        }
        .badge-gray {
            background-color: #F3F4F6;
            color: #374151;
            border: 1px solid #E5E7EB;
        }
        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #EAE1D0;
            font-size: 8.5px;
            color: #A8A29E;
            text-align: center;
            line-height: 1.4;
        }
        .page-break {
            page-break-after: always;
        }
        .price-text {
            font-weight: bold;
            color: #B45309;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table">
        <tr>
            <td style="vertical-align: middle;">
                <h1 class="brand-title">LUMBARONG</h1>
                <div class="brand-sub">Philippine Artisan &amp; Heritage Embroidery Marketplace</div>
            </td>
            <td class="doc-badge" style="vertical-align: middle;">
                <div><strong>OFFICIAL ACCOUNT DATA EXPORT</strong></div>
                <div>Generated: {{ $generatedAt }}</div>
                <div>Account ID: #{{ str_pad($user->id, 5, '0', STR_PAD_LEFT) }}</div>
            </td>
        </tr>
    </table>

    <!-- User Profile Overview -->
    <div class="section-title">1. Account Information</div>
    <table class="info-table">
        <tr>
            <th>Full Name</th>
            <td>{{ $user->name }}</td>
            <th>Username</th>
            <td>{{ $user->username ?: 'N/A' }}</td>
        </tr>
        <tr>
            <th>Email Address</th>
            <td>{{ $user->email }}</td>
            <th>Mobile Number</th>
            <td>{{ $user->mobileNumber ?: 'N/A' }}</td>
        </tr>
        <tr>
            <th>Account Role</th>
            <td><span class="badge badge-gold">{{ ucfirst($user->role) }}</span></td>
            <th>Account Status</th>
            <td><span class="badge {{ $user->status === 'active' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($user->status ?? 'Active') }}</span></td>
        </tr>
        <tr>
            <th>Member Since</th>
            <td>{{ $user->createdAt ? $user->createdAt->format('F d, Y h:i A') : 'N/A' }}</td>
            <th>Verification</th>
            <td>{{ $user->isVerified ? 'Verified Account' : 'Unverified' }}</td>
        </tr>
        @if($user->role === 'customer')
        <tr>
            <th>Gender / Birthday</th>
            <td>{{ $user->gender ?: 'Not specified' }} {{ $user->birthday ? ' | ' . \Carbon\Carbon::parse($user->birthday)->format('M d, Y') : '' }}</td>
            <th>Bio</th>
            <td>{{ $user->bio ?: 'None provided' }}</td>
        </tr>
        @endif
    </table>

    @if($user->role === 'seller')
    <!-- Seller Shop Details -->
    <div class="section-title">2. Artisan Shop Profile &amp; Location</div>
    <table class="info-table">
        <tr>
            <th>Shop Name</th>
            <td><strong>{{ $user->shopName ?: $user->name }}</strong></td>
            <th>Pickup / Business Address</th>
            <td>
                {{ implode(', ', array_filter([
                    $user->shopHouseNo,
                    $user->shopStreet,
                    $user->shopBarangay,
                    $user->shopCity,
                    $user->shopProvince,
                    $user->shopPostalCode
                ])) ?: 'No physical address configured' }}
            </td>
        </tr>
        <tr>
            <th>Shop Story / Bio</th>
            <td colspan="3">{{ $user->shopDescription ?: 'No shop description registered.' }}</td>
        </tr>
        <tr>
            <th>Cancellation Policy</th>
            <td>{{ $user->cancellation_policy ?: 'Standard Marketplace Policy' }}</td>
            <th>Refund Policy</th>
            <td>{{ $user->refund_policy ?: 'Standard Marketplace Policy' }}</td>
        </tr>
    </table>

    <!-- Products Catalog -->
    <div class="section-title">3. Registered Products Catalog ({{ count($products) }})</div>
    @if(count($products) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">ID</th>
                <th style="width: 35%;">Product Name &amp; Description</th>
                <th style="width: 15%;">SKU / Fabric</th>
                <th style="width: 15%;">Price (PHP)</th>
                <th style="width: 10%;">Stock</th>
                <th style="width: 20%;">Status / Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($products as $prod)
            <tr>
                <td>#{{ $prod->id }}</td>
                <td>
                    <strong>{{ $prod->name }}</strong>
                    @if($prod->description)
                    <br><span style="color: #78716C; font-size: 9px;">{{ \Illuminate\Support\Str::limit($prod->description, 70) }}</span>
                    @endif
                </td>
                <td>
                    {{ $prod->sku ?: 'N/A' }}
                    @if($prod->fabric_type)<br><span style="color: #78716C; font-size: 9px;">Fabric: {{ $prod->fabric_type }}</span>@endif
                </td>
                <td class="price-text">PHP {{ number_format($prod->price, 2) }}</td>
                <td>{{ $prod->stock }}</td>
                <td>
                    <span class="badge {{ $prod->status === 'approved' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($prod->status ?? 'Draft') }}</span>
                    <br><span style="color: #A8A29E; font-size: 8.5px;">{{ $prod->createdAt ? $prod->createdAt->format('M d, Y') : '' }}</span>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #78716C; font-style: italic; margin-bottom: 12px;">No products listed yet in this artisan account.</p>
    @endif

    <!-- Seller Order Records -->
    <div class="section-title">4. Fulfilled Customer Orders ({{ count($orders) }})</div>
    @if(count($orders) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Order #</th>
                <th style="width: 16%;">Date</th>
                <th style="width: 36%;">Items Ordered</th>
                <th style="width: 18%;">Payment</th>
                <th style="width: 18%;">Total Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td><strong>#{{ $order->id }}</strong></td>
                <td>{{ $order->createdAt ? $order->createdAt->format('M d, Y') : 'N/A' }}</td>
                <td>
                    @foreach($order->items as $item)
                        <div>• {{ $item->productName }} (x{{ $item->quantity }}) - PHP {{ number_format($item->subtotal ?: ($item->price * $item->quantity), 2) }}</div>
                    @endforeach
                </td>
                <td>
                    {{ strtoupper($order->paymentMethod ?? 'N/A') }}
                    <br><span class="badge badge-gold">{{ ucfirst($order->status) }}</span>
                </td>
                <td class="price-text">PHP {{ number_format($order->totalAmount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #78716C; font-style: italic; margin-bottom: 12px;">No orders fulfilled yet.</p>
    @endif

    <!-- Commission Records -->
    <div class="section-title">5. Commission &amp; Settlement History ({{ count($commissions) }})</div>
    @if(count($commissions) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th>Period</th>
                <th>Total Sales</th>
                <th>Rate</th>
                <th>Commission Amount</th>
                <th>Status</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commissions as $comm)
            <tr>
                <td><strong>{{ $comm->period }}</strong></td>
                <td>PHP {{ number_format($comm->totalSales, 2) }}</td>
                <td>{{ $comm->commissionRate }}%</td>
                <td class="price-text">PHP {{ number_format($comm->commissionAmount, 2) }}</td>
                <td><span class="badge {{ $comm->status === 'settled' || $comm->status === 'paid' ? 'badge-green' : 'badge-gray' }}">{{ ucfirst($comm->status) }}</span></td>
                <td>{{ $comm->createdAt ? $comm->createdAt->format('M d, Y') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #78716C; font-style: italic; margin-bottom: 12px;">No commission history recorded.</p>
    @endif

    @else
    <!-- Customer Addresses -->
    <div class="section-title">2. Saved Shipping Addresses ({{ count($addresses) }})</div>
    @if(count($addresses) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 25%;">Recipient &amp; Contact</th>
                <th style="width: 55%;">Complete Delivery Address</th>
                <th style="width: 20%;">Type / Default</th>
            </tr>
        </thead>
        <tbody>
            @foreach($addresses as $addr)
            <tr>
                <td>
                    <strong>{{ $addr->recipientName }}</strong>
                    <br><span style="color: #78716C; font-size: 9px;">Phone: {{ $addr->phone }}</span>
                </td>
                <td>
                    {{ implode(', ', array_filter([
                        $addr->houseNo,
                        $addr->street,
                        $addr->barangay,
                        $addr->city,
                        $addr->province,
                        $addr->postalCode
                    ])) }}
                </td>
                <td>
                    @if($addr->isDefault)
                        <span class="badge badge-gold">Default Address</span>
                    @else
                        <span class="badge badge-gray">Secondary</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #78716C; font-style: italic; margin-bottom: 12px;">No saved delivery addresses on file.</p>
    @endif

    <!-- Customer Order History -->
    <div class="section-title">3. Order History &amp; Purchases ({{ count($orders) }})</div>
    @if(count($orders) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 12%;">Order #</th>
                <th style="width: 16%;">Order Date</th>
                <th style="width: 36%;">Purchased Items</th>
                <th style="width: 18%;">Payment &amp; Status</th>
                <th style="width: 18%;">Total (PHP)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td><strong>#{{ $order->id }}</strong></td>
                <td>{{ $order->createdAt ? $order->createdAt->format('M d, Y') : 'N/A' }}</td>
                <td>
                    @foreach($order->items as $item)
                        <div>• {{ $item->productName }} (x{{ $item->quantity }}) - PHP {{ number_format($item->subtotal ?: ($item->price * $item->quantity), 2) }}</div>
                    @endforeach
                </td>
                <td>
                    {{ strtoupper($order->paymentMethod ?? 'N/A') }}
                    <br><span class="badge badge-gold">{{ ucfirst($order->status) }}</span>
                </td>
                <td class="price-text">PHP {{ number_format($order->totalAmount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #78716C; font-style: italic; margin-bottom: 12px;">No purchases recorded yet.</p>
    @endif

    <!-- Customer Reviews -->
    <div class="section-title">4. Product Reviews &amp; Ratings ({{ count($reviews) }})</div>
    @if(count($reviews) > 0)
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 35%;">Product</th>
                <th style="width: 15%;">Rating</th>
                <th style="width: 35%;">Feedback / Comment</th>
                <th style="width: 15%;">Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($reviews as $rev)
            <tr>
                <td><strong>{{ $rev->product?->name ?? 'Product' }}</strong></td>
                <td><strong style="color: #C49520;">{{ $rev->rating }} / 5 Stars</strong></td>
                <td>{{ $rev->comment ?: 'No written comment' }}</td>
                <td>{{ $rev->createdAt ? $rev->createdAt->format('M d, Y') : 'N/A' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <p style="color: #78716C; font-style: italic; margin-bottom: 12px;">No product reviews submitted yet.</p>
    @endif

    @endif

    <!-- Security & Privacy Footer -->
    <div class="footer">
        <p><strong>LumBarong Platform Security &amp; Data Privacy Notice</strong><br>
        This official document was generated automatically in compliance with Data Privacy principles. It contains confidential personal information pertaining to the account owner.<br>
        © {{ date('Y') }} LumBarong — All rights reserved.</p>
    </div>

</body>
</html>
