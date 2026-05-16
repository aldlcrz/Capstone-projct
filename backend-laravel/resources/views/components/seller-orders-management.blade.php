<div x-data="sellerOrders()" x-init="init()" class="space-y-8">
    <header class="flex flex-col md:flex-row md:items-end justify-between gap-8">
        <div>
            <div class="flex items-center gap-3 mb-1 sm:mb-2">
                <div class="h-0.5 w-8 sm:w-12 bg-[#C0420A] rounded-full"></div>
                <span class="text-[10px] sm:text-xs font-black text-[#C0420A] tracking-[0.3em] uppercase">Activity History (Workshop Log)</span>
            </div>
            <h1 class="font-serif text-lg sm:text-xl font-bold tracking-tight text-black uppercase">
                ORDER <span class="font-serif italic text-[#C0420A] font-normal ml-1 lowercase">(Registry)</span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-4">
            <div class="relative group">
                <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 group-focus-within:text-[#C0420A] transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input
                    type="text"
                    placeholder="Search orders..."
                    x-model="searchTerm"
                    class="bg-white border border-gray-100 rounded-2xl pl-12 pr-6 py-4 text-sm w-full md:w-[320px] outline-none focus:border-[#C0420A] focus:ring-4 focus:ring-[#C0420A]/5 transition-all shadow-sm"
                />
            </div>
        </div>
    </header>

    <!-- Status Tabs -->
    <div class="border-b border-gray-100 mb-6">
        <div class="grid grid-cols-3 sm:flex sm:flex-row w-full text-center">
            <template x-for="tab in statusTabs" :key="tab">
                <button
                    @click="activeTab = tab"
                    :class="activeTab === tab ? 'bg-[#C0420A]/10 text-[#C0420A] border-[#C0420A]' : 'text-gray-400 border-transparent hover:text-black'"
                    class="py-4 px-2 sm:px-6 text-[10px] font-bold uppercase tracking-widest transition-all border-b-2 sm:flex-1"
                    x-text="tab"
                ></button>
            </template>
        </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-[2.5rem] border border-gray-100 overflow-hidden shadow-xl shadow-stone-200/50">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-stone-50 border-b border-gray-100">
                        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-gray-400">Order ID</th>
                        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-gray-400">Client Info</th>
                        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-center">Status Management</th>
                        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-gray-400">Value / Settlement</th>
                        <th class="px-8 py-5 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-right">Order Log</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <template x-for="(order, idx) in filteredOrders" :key="order.id">
                        <tr class="group hover:bg-stone-50 transition-colors">
                            <td class="px-8 py-6 align-top">
                                <div class="font-serif font-bold text-black group-hover:text-[#C0420A] transition-colors mb-2 tracking-tighter" x-text="'#LB-' + String(order.id).slice(-8).toUpperCase()"></div>
                                <div class="flex items-center gap-1.5 opacity-60">
                                    <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span class="text-[10px] font-bold text-gray-400" x-text="(order.items?.length || 0) + ' Pieces Ordered'"></span>
                                </div>
                            </td>
                            <td class="px-8 py-6 align-top">
                                <div class="flex items-center gap-4">
                                    <div class="w-10 h-10 rounded-full bg-[#3D2B1F] text-white flex items-center justify-center font-bold shadow-md" x-text="(order.customer?.name || 'C')[0]"></div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-xs text-black truncate tracking-tight uppercase" x-text="order.customer?.name || 'Customer'"></div>
                                        <div class="text-[10px] text-gray-400 font-medium italic truncate max-w-[200px]" x-text="formatAddress(order.shippingAddress)"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 align-top text-center">
                                <button
                                    @click="updateStatus(order)"
                                    :disabled="updatingId === order.id"
                                    :class="getStatusClasses(order.status)"
                                    class="px-4 py-1.5 rounded-lg text-[10px] font-black tracking-[0.1em] shadow-sm transition-all border"
                                >
                                    <span x-text="updatingId === order.id ? 'UPDATING...' : (order.status || 'PENDING').toUpperCase()"></span>
                                </button>
                            </td>
                            <td class="px-8 py-6 align-top">
                                <div class="font-bold text-xs text-black" x-text="'₱' + parseFloat(order.totalAmount || 0).toLocaleString()"></div>
                                <div class="text-[11px] font-black text-gray-400 uppercase tracking-widest mt-1" x-text="order.paymentMethod?.toUpperCase()"></div>
                            </td>
                            <td class="px-8 py-6 align-top text-right">
                                <button @click="selectedOrder = order" class="p-2.5 bg-gray-50 text-gray-400 hover:text-white hover:bg-[#C0420A] rounded-xl transition-all shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Order Details Modal (Seller) -->
    <div x-show="selectedOrder" class="fixed inset-0 z-[100] flex items-center justify-center p-4 overflow-hidden" style="display: none;">
        <div @click="selectedOrder = null" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="bg-white w-full max-w-2xl max-h-[88vh] rounded-3xl shadow-2xl relative z-10 overflow-hidden flex flex-col border border-white/20">
            <template x-if="selectedOrder">
                <div class="flex flex-col h-full">
                    <div class="px-6 py-6 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                        <div>
                            <h2 class="font-serif text-xl font-bold text-[#2A2A2A]">Order Details</h2>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mt-1" x-text="'LB-OR-' + String(selectedOrder.id).slice(-8).toUpperCase() + ' • ' + new Date(selectedOrder.createdAt).toLocaleDateString()"></p>
                        </div>
                        <button @click="selectedOrder = null" class="p-2.5 bg-white border border-gray-100 rounded-xl hover:text-[#C0420A] transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18"></path></svg>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto p-6 space-y-8">
                        <!-- Client Info -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-[10px] font-extrabold text-[#C0420A] uppercase tracking-widest">CUSTOMER INFO</div>
                                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 shadow-sm">
                                    <div class="font-bold text-[#2A2A2A] text-sm mb-1" x-text="getParsedAddress(selectedOrder.shippingAddress).name"></div>
                                    <div class="text-[10px] font-bold text-[#C0420A] mb-2" x-text="getParsedAddress(selectedOrder.shippingAddress).phone"></div>
                                    <div class="text-xs text-gray-500 leading-relaxed" x-text="formatAddress(getParsedAddress(selectedOrder.shippingAddress))"></div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-[10px] font-extrabold text-[#C0420A] uppercase tracking-widest">PAYMENT</div>
                                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 shadow-sm">
                                    <div class="font-bold text-[#2A2A2A] text-sm mb-1" x-text="selectedOrder.paymentMethod"></div>
                                    <div class="text-xs text-gray-500">
                                        Reference: <span class="font-mono text-[#C0420A]" x-text="selectedOrder.paymentReference || 'N/A'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 text-[10px] font-extrabold text-[#C0420A] uppercase tracking-widest">ITEMS ORDERED</div>
                            <div class="space-y-3">
                                <template x-for="item in selectedOrder.items" :key="item.id">
                                    <div class="flex gap-4 p-4 bg-white border border-gray-100 rounded-2xl shadow-sm items-center">
                                        <div class="w-14 h-14 bg-gray-50 rounded-xl border border-gray-100 relative shrink-0 overflow-hidden">
                                            <template x-if="item.product && item.product.image">
                                                <img :src="item.product.image.startsWith('http') ? item.product.image : '/storage/' + item.product.image" class="object-cover w-full h-full" />
                                            </template>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-bold text-[#2A2A2A] text-sm truncate" x-text="item.product?.name"></div>
                                            <div class="text-[10px] text-gray-500 mt-0.5 uppercase tracking-wider" x-text="'Qty: ' + item.quantity + ' • Size: ' + item.size"></div>
                                        </div>
                                        <div class="text-sm font-black text-[#C0420A]" x-text="'₱' + parseFloat(item.price).toLocaleString()"></div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-6 bg-gray-50 border-t border-gray-100 flex items-center justify-between">
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">TOTAL VALUE</span>
                        <div class="text-lg font-black text-[#C0420A]" x-text="'₱' + selectedOrder.totalAmount?.toLocaleString()"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@once
<script>
function sellerOrders() {
    return {
        orders: [],
        loading: true,
        activeTab: 'All',
        searchTerm: '',
        updatingId: null,
        selectedOrder: null,
        statusTabs: ["All", "Pending", "To Ship", "To Receive", "Completed", "Cancelled"],
        statusMapping: {
            "Pending": ["Pending"],
            "To Ship": ["Processing"],
            "To Receive": ["Shipped", "Delivered"],
            "Completed": ["Completed"],
            "Cancelled": ["Cancelled", "Cancellation Pending"]
        },
        statusCycle: ["Pending", "Processing", "Shipped", "Delivered", "Completed"],
        init() {
            this.fetchOrders();
        },
        async fetchOrders() {
            this.loading = true;
            try {
                const res = await fetch('/api/v1/seller/orders');
                if (res.ok) {
                    this.orders = await res.json();
                }
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        get filteredOrders() {
            const s = this.searchTerm.toLowerCase();
            return this.orders.filter(order => {
                const matchesTab = this.activeTab === 'All' || 
                    this.statusMapping[this.activeTab]?.some(st => st.toLowerCase() === (order.status || 'Pending').toLowerCase());
                const matchesSearch = String(order.id).includes(s) || 
                    (order.customer?.name || '').toLowerCase().includes(s);
                return matchesTab && matchesSearch;
            });
        },
        getStatusClasses(status) {
            const low = (status || 'Pending').toLowerCase();
            if (low === 'completed' || low === 'delivered') return 'bg-green-50 text-green-700 border-green-100';
            if (low === 'cancelled') return 'bg-red-50 text-red-700 border-red-100';
            return 'bg-amber-50 text-amber-700 border-amber-100';
        },
        getParsedAddress(addr) {
            if (!addr) return {};
            if (typeof addr === 'string') { try { return JSON.parse(addr); } catch (e) { return {}; } }
            return addr;
        },
        formatAddress(addr) {
            const a = this.getParsedAddress(addr);
            if (!a.city) return 'Lumban, Laguna';
            const parts = [a.houseNo, a.street, a.barangay, a.city, a.province];
            return parts.filter(p => p).join(', ') || 'Lumban, Laguna';
        },
        async updateStatus(order) {
            const currentIdx = this.statusCycle.findIndex(s => s.toLowerCase() === (order.status || 'Pending').toLowerCase());
            if (currentIdx === -1 || currentIdx === this.statusCycle.length - 1) return;
            
            const nextStatus = this.statusCycle[currentIdx + 1];
            if (!confirm(`Update order status to ${nextStatus}?`)) return;
            
            this.updatingId = order.id;
            try {
                const res = await fetch(`/api/v1/orders/${order.id}/status`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({ status: nextStatus })
                });
                if (res.ok) this.fetchOrders();
            } catch (e) { console.error(e); }
            this.updatingId = null;
        }
    }
}
</script>
@endonce
