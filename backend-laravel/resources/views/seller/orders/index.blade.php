@extends('layouts.seller')

@section('content')
<script>
function parseOrderAddress(order) {
    let addr = order?.shippingAddress;
    if (!addr) return null;
    if (typeof addr === 'string') {
        try { addr = JSON.parse(addr); } catch (e) { return null; }
    }
    return addr;
}

function formatOrderAddress(order) {
    const addr = parseOrderAddress(order);
    if (!addr) return 'No shipping address provided';
    const lines = [
        addr.recipientName,
        [addr.houseNo, addr.street].filter(Boolean).join(' '),
        addr.barangay,
        [addr.city, addr.province].filter(Boolean).join(', '),
        addr.postalCode ? 'ZIP ' + addr.postalCode : null,
    ].filter(Boolean);
    return lines.join(', ');
}

function buyerOrderPhone(order) {
    const addr = parseOrderAddress(order);
    return addr?.phone || order?.customer?.mobileNumber || 'N/A';
}

function printSellerOrder(order) {
    if (!order) return;

    const orderId = '#LB-' + order.id.slice(-8).toUpperCase();
    const date = order.createdAt
        ? new Date(order.createdAt).toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' })
        : '';

    const itemsHtml = (order.items || []).map(function (item) {
        const variation = item.display_variation && item.display_variation !== 'Original' ? item.display_variation : '—';
        return '<tr>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;">' + (item.product?.name || 'Deleted Product') + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;">' + (item.size || '—') + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;">' + variation + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">' + item.quantity + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">₱' + Number(item.price).toLocaleString() + '</td>'
            + '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">₱' + (Number(item.price) * item.quantity).toLocaleString() + '</td>'
            + '</tr>';
    }).join('');

    const html = '<!DOCTYPE html><html><head><title>Order ' + orderId + '</title>'
        + '<style>'
        + 'body{font-family:Arial,sans-serif;color:#111;padding:32px;max-width:800px;margin:0 auto;}'
        + 'h1{font-size:22px;margin:0 0 4px;}'
        + 'h2{font-size:12px;text-transform:uppercase;letter-spacing:.1em;color:#666;margin:24px 0 8px;}'
        + '.meta{color:#666;font-size:13px;margin-bottom:24px;}'
        + '.box{background:#f9f9f9;border:1px solid #eee;border-radius:8px;padding:16px;margin-bottom:8px;}'
        + 'table{width:100%;border-collapse:collapse;font-size:13px;}'
        + 'th{text-align:left;padding:8px;border-bottom:2px solid #ddd;font-size:11px;text-transform:uppercase;color:#666;}'
        + '.total{text-align:right;font-size:18px;font-weight:bold;margin-top:16px;}'
        + '</style></head><body>'
        + '<h1>LumBarong — Order Details</h1>'
        + '<div class="meta">' + orderId + ' · ' + date + ' · Status: ' + order.status + '</div>'
        + '<h2>Buyer Information</h2>'
        + '<div class="box"><strong>' + (order.customer?.name || 'Unknown Customer') + '</strong><br>'
        + 'Email: ' + (order.customer?.email || 'N/A') + '<br>'
        + 'Phone: ' + buyerOrderPhone(order) + '</div>'
        + '<h2>Shipping Address</h2>'
        + '<div class="box">' + formatOrderAddress(order) + '</div>'
        + '<h2>Product Details</h2>'
        + '<table><thead><tr>'
        + '<th>Product</th><th>Size</th><th>Variation</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th>'
        + '</tr></thead><tbody>' + itemsHtml + '</tbody></table>'
        + '<div class="total">Total: ₱' + Number(order.totalAmount).toLocaleString() + '</div>'
        + '<h2>Payment Information</h2>'
        + '<div class="box">Method: ' + (order.paymentMethod || 'N/A') + '<br>'
        + 'Reference No: ' + (order.paymentReference || 'N/A') + '</div>'
        + '</body></html>';

    const win = window.open('', '_blank');
    win.document.write(html);
    win.document.close();
    win.focus();
    win.print();
}
</script>

<div class="space-y-8" x-data="{
    orders: {{ $orders->toJson() }},
    searchTerm: '',
    statusFilter: 'all',
    activeOrder: null,
    statusModal: false,
    newStatus: '',
    receiptModal: false,
    receiptUrl: '',
    detailsModal: false,
    detailsOrder: null,

    formatAddress(order) {
        return formatOrderAddress(order);
    },

    buyerPhone(order) {
        return buyerOrderPhone(order);
    },

    openDetails(order) {
        this.detailsOrder = order;
        this.detailsModal = true;
    },

    printOrderDetails() {
        printSellerOrder(this.detailsOrder);
    },

    isStatusDisabled(target) {
        if (!this.activeOrder) return true;
        const current = this.activeOrder.status.toLowerCase();
        const t = target.toLowerCase();
        if (current === t) return false;
        if (current === 'completed' || current === 'cancelled') return true;
        
        const states = ['pending', 'processing', 'shipped', 'delivered', 'completed'];
        const currentIdx = states.indexOf(current);
        const targetIdx = states.indexOf(t);
        
        if (currentIdx === -1 || targetIdx === -1) return true;
        return targetIdx < currentIdx; // Disable going backward
    },

    productImage(product) {
        if (!product) return '/uploads/products/default.jpg';
        let rawImg = product.image;
        if (Array.isArray(rawImg)) {
            rawImg = rawImg[0] ?? '';
        }
        if (typeof rawImg === 'string' && rawImg.startsWith('[')) {
            try {
                const parsed = JSON.parse(rawImg);
                rawImg = Array.isArray(parsed) ? (parsed[0] ?? '') : parsed;
            } catch(e) {}
        }
        if (!rawImg) return '/uploads/products/default.jpg';
        if (rawImg.startsWith('http://') || rawImg.startsWith('https://')) {
            return rawImg;
        }
        if (rawImg.startsWith('products/')) {
            return '/storage/' + rawImg;
        }
        if (rawImg.startsWith('uploads/')) {
            return '/' + rawImg;
        }
        if (rawImg.startsWith('/uploads/')) {
            return rawImg;
        }
        return '/uploads/products/' + rawImg;
    },

    get filtered() {
        return this.orders.filter(o => {
            const matchSearch = !this.searchTerm ||
                o.id.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                (o.customer?.name || '').toLowerCase().includes(this.searchTerm.toLowerCase());
            const matchStatus = this.statusFilter === 'all' || o.status.toLowerCase() === this.statusFilter;
            return matchSearch && matchStatus;
        });
    },
    statusColor(s) {
        if (!s) return 'bg-gray-50 text-gray-600 border-gray-200';
        const m = {
            'pending': 'bg-yellow-50 text-yellow-700 border-yellow-200',
            'processing': 'bg-blue-50 text-blue-700 border-blue-200',
            'to ship': 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'to receive': 'bg-purple-50 text-purple-700 border-purple-200',
            'shipped': 'bg-purple-50 text-purple-700 border-purple-200',
            'delivered': 'bg-teal-50 text-teal-700 border-teal-200',
            'completed': 'bg-green-50 text-green-700 border-green-200',
            'cancelled': 'bg-red-50 text-red-700 border-red-200',
        };
        return m[s.toLowerCase()] || 'bg-gray-50 text-gray-600 border-gray-200';
    },
    openStatus(order) {
        this.activeOrder = order;
        this.newStatus = order.status;
        this.statusModal = true;
    },
    async updateStatus() {
        if (!this.activeOrder) return;
        try {
            const res = await fetch('/seller/api/orders/' + this.activeOrder.id + '/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ status: this.newStatus })
            });
            if (res.ok) {
                const idx = this.orders.findIndex(o => o.id === this.activeOrder.id);
                if (idx !== -1) this.orders[idx].status = this.newStatus;
                this.statusModal = false;
                this.activeOrder = null;
            } else {
                alert('Failed to update status. Please try again.');
            }
        } catch(e) {
            alert('Network error. Please try again.');
        }
    }
}">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0420A] uppercase tracking-[0.2em] mb-1">Order Management</div>
            <h1 class="font-serif text-3xl font-bold text-black uppercase">
                My <span class="text-[#C0420A] italic lowercase">orders</span>
            </h1>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative">
                <input type="text" x-model="searchTerm" placeholder="Search by order ID or customer..."
                    class="pl-10 pr-4 py-3 border border-gray-100 bg-white rounded-xl text-xs focus:outline-none focus:ring-2 focus:ring-[#C0420A]/10 w-72">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'completed' => 'Completed'] as $val => $label)
            <button @click="statusFilter = '{{ $val }}'"
                :class="statusFilter === '{{ $val }}' ? 'bg-black text-white' : 'bg-white text-gray-500 border border-gray-100 hover:border-gray-300'"
                class="px-5 py-2.5 rounded-full text-[10px] font-black uppercase tracking-widest whitespace-nowrap transition-all">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Orders Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden">
        <template x-if="filtered.length === 0">
            <div class="py-24 text-center">
                <svg class="w-12 h-12 text-gray-100 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <p class="text-sm font-bold text-gray-300 uppercase tracking-widest">No orders found</p>
            </div>
        </template>

        <div class="divide-y divide-gray-50">
            <template x-for="order in filtered" :key="order.id">
                <div class="p-6 hover:bg-gray-50/50 transition-all">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-start gap-4 flex-1">
                            {{-- Customer avatar --}}
                            <div class="w-10 h-10 rounded-xl bg-[#C0420A] text-white flex items-center justify-center font-black text-sm shrink-0"
                                x-text="(order.customer?.name || '?')[0].toUpperCase()"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="text-xs font-black text-black" x-text="'#LB-' + order.id.slice(-8).toUpperCase()"></span>
                                    <span class="px-2 py-0.5 rounded-full border text-[9px] font-black uppercase"
                                        :class="statusColor(order.status)"
                                        x-text="order.status"></span>
                                </div>
                                <p class="text-[11px] text-gray-400 mt-0.5" x-text="order.customer?.name || 'Unknown Customer'"></p>
                                <p class="text-[10px] text-gray-300 mt-0.5"
                                    x-text="order.items ? order.items.length + ' item(s)' : ''"></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="text-right">
                                <div class="text-base font-black text-[#C0420A]" x-text="'₱' + Number(order.totalAmount).toLocaleString()"></div>
                                <div class="text-[10px] text-gray-300" x-text="order.createdAt ? new Date(order.createdAt).toLocaleDateString('en-PH', {month:'short', day:'numeric', year:'numeric'}) : ''"></div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="openDetails(order)"
                                    class="px-4 py-2.5 bg-white border border-gray-200 text-gray-700 rounded-xl text-[9px] font-black uppercase tracking-widest hover:border-[#C0420A] hover:text-[#C0420A] transition-all whitespace-nowrap">
                                    Order Details
                                </button>
                                <button @click="openStatus(order)"
                                    class="px-5 py-2.5 bg-black text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-[#C0420A] transition-all whitespace-nowrap">
                                    Update Status
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Detailed items and payment block --}}
                    <template x-if="order.items && order.items.length > 0">
                        <div class="mt-4 pt-4 border-t border-gray-100 grid grid-cols-1 lg:grid-cols-12 gap-6 ml-0 md:ml-14">
                            
                            {{-- Purchased Items list --}}
                            <div class="lg:col-span-8 flex flex-col min-h-0">
                                <div class="flex items-center justify-between mb-2 shrink-0">
                                    <div class="text-[9px] font-black uppercase tracking-widest text-gray-400">Purchased Items</div>
                                    <span class="text-[9px] font-bold text-gray-300 uppercase tracking-widest"
                                          x-text="order.items.length + ' item' + (order.items.length !== 1 ? 's' : '')"></span>
                                </div>
                                <div class="max-h-64 overflow-y-auto pr-1 space-y-3 scroll-smooth
                                            [&::-webkit-scrollbar]:w-1.5
                                            [&::-webkit-scrollbar-track]:bg-gray-50
                                            [&::-webkit-scrollbar-track]:rounded-full
                                            [&::-webkit-scrollbar-thumb]:bg-gray-200
                                            [&::-webkit-scrollbar-thumb]:rounded-full
                                            hover:[&::-webkit-scrollbar-thumb]:bg-gray-300">
                                    <template x-for="item in order.items" :key="item.id">
                                        <div class="flex items-center gap-3 bg-gray-50/50 border border-gray-100 rounded-xl p-3">
                                            {{-- Thumbnail --}}
                                            <div class="w-10 h-12 bg-gray-100 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                                <img :src="productImage(item.product)" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'">
                                            </div>
                                            {{-- Info --}}
                                            <div class="flex-1 min-w-0">
                                                <h4 class="text-xs font-bold text-black truncate" x-text="item.product?.name || 'Deleted Product'"></h4>
                                                <div class="flex flex-wrap gap-2 text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                                                    <span x-show="item.size" x-text="'Size: ' + item.size"></span>
                                                    <span x-show="item.display_variation && item.display_variation !== 'Original'" x-text="'Variation: ' + item.display_variation"></span>
                                                </div>
                                            </div>
                                            {{-- Price details --}}
                                            <div class="text-right shrink-0">
                                                <div class="text-xs font-bold text-black" x-text="item.quantity + ' x ₱' + Number(item.price).toLocaleString()"></div>
                                                <div class="text-[9px] text-gray-400" x-text="'₱' + (Number(item.price) * item.quantity).toLocaleString() + ' subtotal'"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Payment details --}}
                            <div class="lg:col-span-4 bg-gray-50/40 border border-gray-100 rounded-2xl p-4 space-y-3">
                                <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-1">Payment Information</div>
                                <div class="space-y-2 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Method:</span>
                                        <span class="font-black text-black uppercase" x-text="order.paymentMethod || 'N/A'"></span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Reference No:</span>
                                        <span class="font-mono text-xs font-bold text-gray-700" x-text="order.paymentReference || 'N/A'"></span>
                                    </div>
                                    <template x-if="order.paymentProof">
                                        <div class="pt-2 border-t border-gray-100 flex flex-col gap-1.5">
                                            <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Proof of Payment:</span>
                                            <button type="button" @click="receiptUrl = '/storage/' + order.paymentProof; receiptModal = true;" class="inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest text-[#C0420A] hover:underline cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                View Receipt
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    {{-- Order Details Modal --}}
    <div x-show="detailsModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
        <div @click.away="detailsModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-8 space-y-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 class="font-serif text-xl font-bold text-black mb-1">Order Details</h3>
                    <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold"
                       x-text="detailsOrder ? '#LB-' + detailsOrder.id.slice(-8).toUpperCase() : ''"></p>
                </div>
                <button type="button" @click="detailsModal = false"
                    class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <template x-if="detailsOrder">
                <div class="space-y-6">
                    {{-- Buyer --}}
                    <div>
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Buyer Information</div>
                        <div class="bg-gray-50/60 border border-gray-100 rounded-2xl p-4 space-y-1 text-sm">
                            <p class="font-bold text-black" x-text="detailsOrder.customer?.name || 'Unknown Customer'"></p>
                            <p class="text-gray-500"><span class="font-bold text-gray-400 text-[10px] uppercase">Email:</span> <span x-text="detailsOrder.customer?.email || 'N/A'"></span></p>
                            <p class="text-gray-500"><span class="font-bold text-gray-400 text-[10px] uppercase">Phone:</span> <span x-text="buyerPhone(detailsOrder)"></span></p>
                        </div>
                    </div>

                    {{-- Address --}}
                    <div>
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Shipping Address</div>
                        <div class="bg-gray-50/60 border border-gray-100 rounded-2xl p-4 text-sm text-gray-700 leading-relaxed"
                             x-text="formatAddress(detailsOrder)"></div>
                    </div>

                    {{-- Products --}}
                    <div>
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Product Details</div>
                        <div class="space-y-2">
                            <template x-for="item in detailsOrder.items || []" :key="item.id">
                                <div class="flex items-center gap-3 bg-gray-50/60 border border-gray-100 rounded-xl p-3">
                                    <div class="w-10 h-12 bg-gray-100 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                        <img :src="productImage(item.product)" class="w-full h-full object-cover object-top" onerror="this.src='/uploads/products/default.jpg'">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs font-bold text-black truncate" x-text="item.product?.name || 'Deleted Product'"></h4>
                                        <div class="flex flex-wrap gap-2 text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-0.5">
                                            <span x-show="item.size" x-text="'Size: ' + item.size"></span>
                                            <span x-show="item.display_variation && item.display_variation !== 'Original'" x-text="'Variation: ' + item.display_variation"></span>
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div class="text-xs font-bold text-black" x-text="item.quantity + ' x ₱' + Number(item.price).toLocaleString()"></div>
                                        <div class="text-[9px] text-gray-400" x-text="'₱' + (Number(item.price) * item.quantity).toLocaleString()"></div>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-between items-center pt-4 mt-2 border-t border-gray-100">
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Order Total</span>
                            <span class="text-lg font-black text-[#C0420A]" x-text="'₱' + Number(detailsOrder.totalAmount).toLocaleString()"></span>
                        </div>
                    </div>

                    {{-- Payment --}}
                    <div>
                        <div class="text-[9px] font-black uppercase tracking-widest text-gray-400 mb-2">Payment Information</div>
                        <div class="bg-gray-50/60 border border-gray-100 rounded-2xl p-4 space-y-1 text-sm">
                            <p class="text-gray-500"><span class="font-bold text-gray-400 text-[10px] uppercase">Method:</span> <span class="font-bold text-black uppercase" x-text="detailsOrder.paymentMethod || 'N/A'"></span></p>
                            <p class="text-gray-500"><span class="font-bold text-gray-400 text-[10px] uppercase">Reference:</span> <span class="font-mono" x-text="detailsOrder.paymentReference || 'N/A'"></span></p>
                        </div>
                    </div>
                </div>
            </template>

            <div class="flex gap-3 pt-2">
                <button @click="detailsModal = false"
                    class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                    Close
                </button>
                <button @click="printOrderDetails()"
                    class="flex-1 py-3 bg-black text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0420A] transition-all flex items-center justify-center gap-2">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print / Save PDF
                </button>
            </div>
        </div>
    </div>

    {{-- Status Update Modal --}}
    <div x-show="statusModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" x-cloak>
        <div @click.away="statusModal = false" class="bg-white rounded-3xl shadow-2xl w-full max-w-md p-8 space-y-6">
            <div>
                <h3 class="font-serif text-xl font-bold text-black mb-1">Update Order Status</h3>
                <p class="text-[10px] text-gray-400 uppercase tracking-widest font-bold" x-text="activeOrder ? '#LB-' + activeOrder.id.slice(-8).toUpperCase() : ''"></p>
            </div>

            <div class="space-y-3">
                @foreach(['Pending', 'Processing', 'Shipped', 'Delivered'] as $s)
                    <label class="flex items-center gap-3 p-4 rounded-xl border transition-all"
                        :class="{
                            'border-[#C0420A] bg-red-50': newStatus === '{{ $s }}',
                            'border-gray-100 hover:border-gray-300': newStatus !== '{{ $s }}' && !isStatusDisabled('{{ $s }}'),
                            'opacity-40 cursor-not-allowed bg-gray-50 border-gray-100': isStatusDisabled('{{ $s }}')
                        }">
                        <input type="radio" x-model="newStatus" value="{{ $s }}" class="accent-[#C0420A]" :disabled="isStatusDisabled('{{ $s }}')">
                        <span class="text-xs font-bold text-black uppercase tracking-wider">{{ $s }}</span>
                    </label>
                @endforeach
            </div>

            <div class="flex gap-3">
                <button @click="statusModal = false"
                    class="flex-1 py-3 rounded-xl border border-gray-200 text-[10px] font-bold uppercase tracking-widest text-gray-500 hover:bg-gray-50 transition-all">
                    Cancel
                </button>
                <button @click="updateStatus()"
                    :disabled="!activeOrder || newStatus === activeOrder.status"
                    :class="(!activeOrder || newStatus === activeOrder.status) ? 'opacity-50 cursor-not-allowed hover:bg-black' : ''"
                    class="flex-1 py-3 bg-black text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0420A] transition-all">
                    Save Status
                </button>
            </div>
        </div>
    </div>

    {{-- Floating Receipt Image Modal --}}
    <div x-show="receiptModal" class="fixed inset-0 z-9999 flex items-center justify-center p-4 bg-black/60 backdrop-blur-md" x-cloak>
        <div @click.away="receiptModal = false" class="relative max-w-lg w-full bg-white rounded-3xl overflow-hidden shadow-2xl p-6 flex flex-col items-center">
            <div class="w-full flex items-center justify-between pb-4 border-b border-gray-100 mb-4">
                <h3 class="font-serif text-lg font-bold text-black">Proof of Payment</h3>
                <button type="button" @click="receiptModal = false"
                    class="w-8 h-8 rounded-full border border-gray-200 flex items-center justify-center text-gray-400 hover:text-black hover:border-gray-400 transition-all shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            
            <div class="w-full bg-gray-50 rounded-2xl overflow-hidden flex items-center justify-center border border-gray-100 max-h-[70vh]">
                <img :src="receiptUrl" class="max-w-full max-h-[60vh] object-contain" alt="Payment Proof">
            </div>
            
            <div class="w-full mt-4 flex gap-3">
                <a :href="receiptUrl" download="receipt" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                    Download
                </a>
                <button type="button" @click="receiptModal = false" class="flex-1 py-3 bg-black hover:bg-[#C0420A] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all">
                    Close
                </button>
            </div>
        </div>
    </div>
    <!-- Floating Scroll Navigator -->
    <div 
        x-data="{
            showScrollTop: false,
            showScrollBottom: true,
            container: null,
            initNavigator() {
                this.container = document.querySelector('main');
                if (this.container) {
                    this.container.addEventListener('scroll', () => this.checkScroll());
                    // Run checkScroll after next tick to ensure DOM is fully rendered
                    this.$nextTick(() => this.checkScroll());
                }
            },
            checkScroll() {
                if (!this.container) return;
                const scrolled = this.container.scrollTop;
                const maxScroll = this.container.scrollHeight - this.container.clientHeight;
                this.showScrollTop = scrolled > 100;
                this.showScrollBottom = maxScroll > 0 && scrolled < maxScroll - 100;
            },
            scrollToTop() {
                if (this.container) {
                    this.container.scrollTo({ top: 0, behavior: 'smooth' });
                }
            },
            scrollToBottom() {
                if (this.container) {
                    this.container.scrollTo({ top: this.container.scrollHeight, behavior: 'smooth' });
                }
            }
        }"
        x-init="initNavigator()"
        class="fixed bottom-6 right-6 z-50 flex flex-col gap-2"
    >
        <!-- Scroll to Top Button -->
        <button 
            x-show="showScrollTop"
            @click="scrollToTop()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-4"
            class="w-12 h-12 bg-black hover:bg-[#C0422A] text-white rounded-full flex items-center justify-center shadow-lg transition-all"
            title="Scroll to Top"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"></path>
            </svg>
        </button>

        <!-- Scroll to Bottom Button -->
        <button 
            x-show="showScrollBottom"
            @click="scrollToBottom()"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 -translate-y-4"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-4"
            class="w-12 h-12 bg-black hover:bg-[#C0422A] text-white rounded-full flex items-center justify-center shadow-lg transition-all"
            title="Scroll to Bottom"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
    </div>
</div>
@endsection
