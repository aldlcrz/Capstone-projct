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

<div class="space-y-4 sm:space-y-6 max-w-5xl pb-28 lg:pb-12 px-2 sm:px-6" x-data="{
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
        this.newStatus = order.status;
        this.detailsModal = true;
    },

    openStatus(order) {
        this.activeOrder = order;
        this.newStatus = order.status;
        this.statusModal = true;
    },

    printOrderDetails() {
        printSellerOrder(this.detailsOrder);
    },

    isStatusDisabled(target, currentOrder) {
        const order = currentOrder || this.activeOrder || this.detailsOrder;
        if (!order) return true;
        const current = (order.status || '').toLowerCase();
        const t = target.toLowerCase();
        if (current === t) return false;
        if (current === 'completed' || current === 'cancelled') return true;
        
        const states = ['pending', 'processing', 'shipped', 'delivered', 'completed'];
        const currentIdx = states.indexOf(current);
        const targetIdx = states.indexOf(t);
        
        if (currentIdx === -1 || targetIdx === -1) return true;
        return targetIdx < currentIdx;
    },

    productImage(product) {
        if (!product) return '/uploads/products/default.jpg';
        let rawImg = product.image;
        if (Array.isArray(rawImg)) {
            rawImg = rawImg[0] ?? '';
        }
        if (typeof rawImg === 'string' && (rawImg.startsWith('[') || rawImg.startsWith('{'))) {
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
            'pending': 'bg-amber-50 text-amber-700 border-amber-200',
            'processing': 'bg-blue-50 text-blue-700 border-blue-200',
            'to ship': 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'shipped': 'bg-purple-50 text-purple-700 border-purple-200',
            'delivered': 'bg-teal-50 text-teal-700 border-teal-200',
            'completed': 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled': 'bg-red-50 text-red-700 border-red-200',
        };
        return m[s.toLowerCase()] || 'bg-gray-50 text-gray-600 border-gray-200';
    },

    async updateStatus(targetOrder, statusToSave) {
        const target = targetOrder || this.detailsOrder || this.activeOrder;
        const statusVal = statusToSave || this.newStatus;
        if (!target || !statusVal) return;

        try {
            const res = await fetch('/seller/api/orders/' + target.id + '/status', {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ status: statusVal })
            });
            if (res.ok) {
                const idx = this.orders.findIndex(o => o.id === target.id);
                if (idx !== -1) {
                    this.orders[idx].status = statusVal;
                    if (this.detailsOrder && this.detailsOrder.id === target.id) {
                        this.detailsOrder.status = statusVal;
                    }
                }
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

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
            <div class="text-[9px] sm:text-[10px] font-bold text-[#C0420A] uppercase tracking-[0.2em] mb-0.5">Order Management</div>
            <h1 class="font-serif text-xl sm:text-3xl font-bold text-black uppercase">
                My <span class="text-[#C0420A] italic lowercase">orders</span>
            </h1>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Tap any order capsule to view complete items, buyer details, and update status.</p>
        </div>
        
        {{-- Search Input --}}
        <div class="relative w-full sm:w-72">
            <input type="text" x-model="searchTerm" placeholder="Search order ID or customer..."
                class="w-full h-10 sm:h-11 pl-9 sm:pl-10 pr-4 bg-white border border-gray-200 rounded-full text-xs font-semibold shadow-sm outline-none focus:border-[#C0420A] transition-all">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3 sm:top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    {{-- Status Filter Tabs (Capsules) --}}
    <div class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
            <button @click="statusFilter = '{{ $val }}'"
                :class="statusFilter === '{{ $val }}' ? 'bg-black text-white shadow-md' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300'"
                class="px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-full text-[9px] sm:text-[10px] font-black uppercase tracking-widest whitespace-nowrap transition-all flex items-center gap-1.5 shrink-0 active:scale-95">
                <span>{{ $label }}</span>
                @if(isset($counts[$val]))
                    <span class="px-1.5 py-0.5 text-[8px] sm:text-[9px] rounded-full" :class="statusFilter === '{{ $val }}' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'">{{ $counts[$val] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Order Capsule List (Pill Layout like Customer Directory) --}}
    <div class="space-y-2.5 sm:space-y-3">
        <template x-if="filtered.length === 0">
            <div class="bg-white rounded-3xl p-10 text-center space-y-2 border border-gray-100 shadow-sm">
                <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto text-xl">🛍️</div>
                <h3 class="text-xs sm:text-sm font-black text-black uppercase tracking-wider">No Orders Found</h3>
                <p class="text-[11px] text-gray-400">When customers place orders matching this filter, they will appear here as clickable capsules.</p>
            </div>
        </template>

        <template x-for="order in filtered" :key="order.id">
            <div @click="openDetails(order)"
                 class="group bg-white hover:bg-gray-50/80 rounded-full p-2.5 sm:p-3.5 px-4 sm:px-6 border border-gray-100 hover:border-[#C0420A]/40 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex items-center justify-between gap-3 active:scale-[0.99]">
                
                {{-- Left: Avatar & Order Info --}}
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-linear-to-tr from-[#3D2B1F] to-[#C0420A] flex items-center justify-center text-white font-black text-xs sm:text-base shadow-sm shrink-0 overflow-hidden group-hover:scale-105 transition-transform">
                        <span x-text="(order.customer?.name || 'O')[0].toUpperCase()"></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-xs sm:text-sm font-black text-black truncate uppercase tracking-tight group-hover:text-[#C0420A] transition-colors"
                                x-text="'#LB-' + order.id.slice(-8).toUpperCase()"></h3>
                            <span class="px-2.5 py-0.5 rounded-full border text-[8px] sm:text-[9px] font-black uppercase tracking-wider shrink-0"
                                  :class="statusColor(order.status)"
                                  x-text="order.status"></span>
                        </div>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 truncate font-medium mt-0.5">
                            <span class="font-bold text-gray-600" x-text="order.customer?.name || 'Customer'"></span>
                            <span class="text-gray-300"> • </span>
                            <span x-text="(order.items ? order.items.length : 0) + ' item' + (order.items && order.items.length !== 1 ? 's' : '')"></span>
                        </p>
                    </div>
                </div>

                {{-- Right: Total Price & Navigation Arrow Pill --}}
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <div class="text-[8px] sm:text-[9px] font-bold text-gray-400 uppercase tracking-widest"
                             x-text="order.createdAt ? new Date(order.createdAt).toLocaleDateString('en-PH', {month:'short', day:'numeric'}) : ''"></div>
                        <div class="text-xs sm:text-sm font-black text-[#C0420A]" x-text="'₱' + Number(order.totalAmount).toLocaleString()"></div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-[#C0420A] group-hover:text-white flex items-center justify-center text-gray-400 transition-all shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Order Detail Modal (Pill Details Bottom Sheet / Modal) --}}
    <div x-show="detailsModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        
        <div @click.away="detailsModal = false" 
             class="w-full sm:max-w-xl bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            {{-- Modal Header Banner --}}
            <div class="relative bg-linear-to-br from-[#2A2A28] to-black p-6 text-white text-center shrink-0">
                <button @click="detailsModal = false" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                
                {{-- Order Icon Circle --}}
                <div class="w-14 h-14 rounded-full bg-linear-to-tr from-[#3D2B1F] to-[#C0420A] flex items-center justify-center text-white font-black text-xl shadow-lg border-2 border-white/20 mx-auto mb-2 overflow-hidden">
                    🛍️
                </div>
                <h2 class="text-base sm:text-lg font-black uppercase tracking-tight" x-text="detailsOrder ? '#LB-' + detailsOrder.id.slice(-8).toUpperCase() : 'Order Details'"></h2>
                <p class="text-xs text-gray-300 font-medium mt-0.5" x-text="detailsOrder &amp;&amp; detailsOrder.createdAt ? new Date(detailsOrder.createdAt).toLocaleDateString('en-PH', {month:'long', day:'numeric', year:'numeric'}) : ''"></p>
                
                <div class="mt-2.5 inline-flex items-center gap-2 px-3 py-1 bg-white/10 rounded-full text-[10px] font-bold uppercase tracking-widest">
                    <span>Status:</span>
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase" :class="statusColor(detailsOrder?.status)" x-text="detailsOrder?.status"></span>
                </div>
            </div>

            {{-- Modal Body Content --}}
            <div class="p-5 sm:p-6 overflow-y-auto flex-1 space-y-5">
                <template x-if="detailsOrder">
                    <div class="space-y-5">
                        
                        {{-- Quick Status Update Row --}}
                        <div class="p-4 bg-amber-50/60 border border-amber-100 rounded-2xl space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-black uppercase tracking-wider text-amber-800">Update Order Status</span>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest" x-text="'Current: ' + detailsOrder.status"></span>
                            </div>
                            <div class="flex flex-wrap gap-1.5 pt-1">
                                @foreach(['Pending', 'Processing', 'Shipped', 'Delivered', 'Completed', 'Cancelled'] as $st)
                                    <button type="button"
                                        @click="updateStatus(detailsOrder, '{{ $st }}')"
                                        :disabled="isStatusDisabled('{{ $st }}', detailsOrder)"
                                        :class="{
                                            'bg-[#C0420A] text-white font-black shadow-sm': detailsOrder.status.toLowerCase() === '{{ strtolower($st) }}',
                                            'bg-white text-gray-700 hover:border-[#C0420A] border border-gray-200': detailsOrder.status.toLowerCase() !== '{{ strtolower($st) }}' && !isStatusDisabled('{{ $st }}', detailsOrder),
                                            'opacity-30 cursor-not-allowed bg-gray-100 text-gray-400 border-gray-100': isStatusDisabled('{{ $st }}', detailsOrder)
                                        }"
                                        class="px-3 py-1.5 rounded-full text-[9px] font-bold uppercase tracking-wider transition-all">
                                        {{ $st }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Buyer & Shipping Info Card --}}
                        <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 space-y-3">
                            <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Buyer & Shipping Details</div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Customer Name</div>
                                    <div class="font-black text-black mt-0.5" x-text="detailsOrder.customer?.name || 'Unknown Buyer'"></div>
                                </div>
                                <div>
                                    <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Phone Contact</div>
                                    <div class="font-bold text-black mt-0.5" x-text="buyerPhone(detailsOrder)"></div>
                                </div>
                            </div>

                            <div class="pt-2 border-t border-gray-200/60 text-xs">
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Delivery Address</div>
                                <div class="text-gray-700 font-medium mt-0.5 leading-relaxed" x-text="formatAddress(detailsOrder)"></div>
                            </div>
                        </div>

                        {{-- Purchased Product Items --}}
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Purchased Items</div>
                                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest"
                                      x-text="(detailsOrder.items ? detailsOrder.items.length : 0) + ' item(s)'"></span>
                            </div>

                            <div class="space-y-2">
                                <template x-for="item in detailsOrder.items || []" :key="item.id">
                                    <div class="flex items-center gap-3 bg-white border border-gray-100 rounded-2xl p-3 shadow-xs">
                                        <div class="w-12 h-14 bg-gray-50 rounded-xl overflow-hidden shrink-0 border border-gray-100">
                                            <img :src="productImage(item.product)" class="w-full h-full object-cover object-top" x-on:error="$event.target.src='/uploads/products/default.jpg'">
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-xs font-bold text-black truncate" x-text="item.product?.name || 'Product Item'"></h4>
                                            <div class="flex flex-wrap gap-2 text-[9px] font-bold text-gray-400 uppercase tracking-widest mt-1">
                                                <span x-show="item.size" x-text="'Size: ' + item.size"></span>
                                                <span x-show="item.display_variation && item.display_variation !== 'Original'" x-text="'Variation: ' + item.display_variation"></span>
                                            </div>
                                        </div>
                                        <div class="text-right shrink-0">
                                            <div class="text-xs font-black text-black" x-text="item.quantity + ' × ₱' + Number(item.price).toLocaleString()"></div>
                                            <div class="text-[9px] font-bold text-[#C0420A]" x-text="'₱' + (Number(item.price) * item.quantity).toLocaleString()"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-2xl border border-gray-100">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Grand Total Amount</span>
                                <span class="text-base font-black text-[#C0420A]" x-text="'₱' + Number(detailsOrder.totalAmount).toLocaleString(undefined, {minimumFractionDigits: 2})"></span>
                            </div>
                        </div>

                        {{-- Payment Information --}}
                        <div class="bg-gray-50/80 p-4 rounded-2xl border border-gray-100 space-y-2 text-xs">
                            <div class="text-[9px] font-black uppercase tracking-widest text-[#C0420A]">Payment Details</div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Method</span>
                                <span class="font-black text-black uppercase" x-text="detailsOrder.paymentMethod || 'COD'"></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Reference No.</span>
                                <span class="font-mono text-xs font-bold text-gray-700" x-text="detailsOrder.paymentReference || 'N/A'"></span>
                            </div>
                            <template x-if="detailsOrder.paymentProof">
                                <div class="pt-2 border-t border-gray-200/60 flex items-center justify-between">
                                    <span class="text-gray-400 font-bold text-[9px] uppercase tracking-wider">Receipt File</span>
                                    <button type="button" @click="receiptUrl = '/storage/' + detailsOrder.paymentProof; receiptModal = true;" class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-widest text-[#C0420A] hover:underline">
                                        View Proof ↗
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Modal Footer Actions --}}
            <div class="p-4 sm:p-5 bg-gray-50 border-t border-gray-100 flex gap-3 shrink-0">
                <button @click="detailsModal = false"
                    class="flex-1 py-2.5 sm:py-3 rounded-full border border-gray-200 bg-white text-[10px] font-black uppercase tracking-widest text-gray-600 hover:bg-gray-100 transition-all">
                    Close
                </button>
                <button @click="printOrderDetails()"
                    class="flex-1 py-2.5 sm:py-3 bg-black text-white text-[10px] font-black uppercase tracking-widest hover:bg-[#C0420A] rounded-full transition-all flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print PDF
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
        class="fixed bottom-20 sm:bottom-6 right-4 sm:right-6 z-50 flex flex-col gap-2"
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
            class="w-12 h-12 bg-black hover:bg-[#C0420A] text-white rounded-full flex items-center justify-center shadow-lg transition-all"
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
            class="w-12 h-12 bg-black hover:bg-[#C0420A] text-white rounded-full flex items-center justify-center shadow-lg transition-all"
            title="Scroll to Bottom"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
    </div>
</div>
@endsection
