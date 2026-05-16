<div x-data="ordersManagement()" x-init="init()" class="space-y-6">
    <!-- Header/Search -->
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4 pb-4">
        <div class="space-y-2">
            <h2 class="font-serif text-lg font-bold text-[#2A2A2A]">Order Management</h2>
            <p class="text-xs text-gray-400">Track and manage your heritage purchases</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="relative group flex-1 sm:w-64">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                <input
                    type="text"
                    x-model="searchQuery"
                    placeholder="Search orders..."
                    class="w-full pl-9 pr-4 py-2 bg-white border border-gray-100 rounded-xl text-[11px] outline-none focus:border-[#C0420A] transition-all shadow-sm"
                />
            </div>
            <button @click="fetchOrders" class="p-2 bg-white border border-gray-100 rounded-xl hover:text-[#C0420A] transition-colors shadow-sm">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 overflow-x-auto no-scrollbar pb-2 border-b border-gray-100">
        <template x-for="tab in tabs" :key="tab.key">
            <button
                @click="activeTab = tab.key"
                :class="activeTab === tab.key ? 'border-[#C0420A] text-[#C0420A]' : 'border-transparent text-gray-400 hover:text-black'"
                class="px-4 py-2 text-[10px] font-bold uppercase tracking-widest transition-all whitespace-nowrap border-b-2"
                x-text="tab.label"
            ></button>
        </template>
    </div>

    <!-- Order List -->
    <div class="space-y-4">
        <template x-if="loading && orders.length === 0">
            <div class="py-20 text-center">
                <svg class="w-8 h-8 animate-spin mx-auto text-gray-200 mb-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Syncing Orders...</p>
            </div>
        </template>
        
        <template x-if="!loading && filteredOrders.length === 0">
            <div class="py-16 text-center bg-white rounded-3xl border border-gray-100 shadow-sm">
                <svg class="w-12 h-12 mx-auto text-gray-100 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                <h3 class="text-base font-serif font-bold text-[#2A2A2A] mb-1">No Orders Found</h3>
                <p class="text-[10px] text-gray-400 italic">Your heritage shopping journey starts here.</p>
            </div>
        </template>

        <template x-for="order in filteredOrders" :key="order.id">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                    <div class="text-[10px] font-bold text-gray-400" x-text="'ORDER LB-OR-' + String(order.id).slice(-8).toUpperCase()"></div>
                    <div 
                        class="px-2 py-0.5 rounded-full border text-[8px] font-black uppercase tracking-widest bg-white"
                        :class="getStatusClasses(order.status)"
                        x-text="getDisplayStatus(order.status)"
                    ></div>
                </div>
                <div class="p-4 space-y-3">
                    <template x-for="item in order.items" :key="item.id">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gray-50 rounded-lg overflow-hidden shrink-0 border border-gray-100 relative">
                                <template x-if="item.product && item.product.image">
                                    <img :src="item.product.image.startsWith('http') ? item.product.image : '/storage/' + item.product.image" class="object-cover w-full h-full" />
                                </template>
                                <template x-if="!item.product || !item.product.image">
                                    <svg class="w-4 h-4 text-gray-200 absolute inset-0 m-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                                </template>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-xs font-bold text-[#2A2A2A] truncate" x-text="item.product?.name"></div>
                                <div class="text-[9px] text-gray-400 uppercase tracking-tighter" x-text="'Qty: ' + item.quantity + ' • Size: ' + item.size"></div>
                            </div>
                            <div class="text-sm font-black text-[#C0420A]" x-text="'₱' + parseFloat(item.price).toLocaleString()"></div>
                        </div>
                    </template>
                </div>
                <div class="px-5 py-3 border-t border-gray-50 flex items-center justify-between bg-white">
                    <div class="text-[10px] text-gray-400">Total: <span class="font-bold text-[#2A2A2A]" x-text="'₱' + order.totalAmount?.toLocaleString()"></span></div>
                    <button @click="selectedOrder = order" class="text-[10px] font-bold text-[#C0420A] hover:underline">View Details</button>
                </div>
            </div>
        </template>
    </div>

    <!-- Order Details Modal -->
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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-[10px] font-extrabold text-[#C0420A] uppercase tracking-widest">SHIPPING ADDRESS</div>
                                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 shadow-sm">
                                    <div class="font-bold text-[#2A2A2A] text-sm mb-1" x-text="getParsedAddress(selectedOrder.shippingAddress).name"></div>
                                    <div class="text-[10px] font-bold text-[#C0420A] mb-2" x-text="getParsedAddress(selectedOrder.shippingAddress).phone"></div>
                                    <div class="text-xs text-gray-500 leading-relaxed" x-text="formatAddress(getParsedAddress(selectedOrder.shippingAddress))"></div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex items-center gap-2 text-[10px] font-extrabold text-[#C0420A] uppercase tracking-widest">PAYMENT METHOD</div>
                                <div class="bg-gray-50 p-4 rounded-2xl border border-gray-100 shadow-sm">
                                    <div class="font-bold text-[#2A2A2A] text-sm mb-1" x-text="selectedOrder.paymentMethod"></div>
                                    <div class="text-xs text-gray-500">
                                        Reference: <span class="font-mono text-[#C0420A]" x-text="selectedOrder.paymentReference || 'N/A'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-2 text-[10px] font-extrabold text-[#C0420A] uppercase tracking-widest">PURCHASED ITEMS</div>
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
                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">TOTAL AMOUNT</span>
                        <div class="text-lg font-black text-[#C0420A]" x-text="'₱' + selectedOrder.totalAmount?.toLocaleString()"></div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

@once
<script>
function ordersManagement() {
    return {
        view: 'list',
        orders: [],
        loading: true,
        activeTab: 'ALL',
        searchQuery: '',
        selectedOrder: null,
        tabs: [
            { key: 'ALL', label: 'ALL' },
            { key: 'PENDING', label: 'PENDING' },
            { key: 'TO SHIP', label: 'TO SHIP' },
            { key: 'TO RECEIVE', label: 'TO RECEIVE' },
            { key: 'COMPLETED', label: 'COMPLETED' },
            { key: 'CANCELLED', label: 'CANCELLED' },
        ],
        init() {
            this.fetchOrders();
        },
        async fetchOrders() {
            this.loading = true;
            try {
                const res = await fetch('/api/v1/orders/my-orders');
                if (res.ok) {
                    this.orders = await res.json();
                }
            } catch (e) { console.error(e); }
            this.loading = false;
        },
        get filteredOrders() {
            const s = this.searchQuery.toLowerCase();
            return this.orders.filter(order => {
                const matchesTab = this.activeTab === 'ALL' || this.getDisplayStatus(order.status) === this.activeTab;
                const matchesSearch = String(order.id).includes(s) || 
                    order.items?.some(i => i.product?.name?.toLowerCase().includes(s));
                return matchesTab && matchesSearch;
            });
        },
        getDisplayStatus(status) {
            switch (status?.toLowerCase()) {
                case "pending": return "PENDING";
                case "processing":
                case "to ship": return "TO SHIP";
                case "shipped":
                case "to receive": return "TO RECEIVE";
                case "delivered": return "DELIVERED";
                case "received by buyer": return "RECEIVED BY BUYER";
                case "completed": return "COMPLETED";
                case "cancelled": return "CANCELLED";
                case "cancellation pending": return "CANCELLATION PENDING";
                default: return "PENDING";
            }
        },
        getStatusClasses(status) {
            const s = this.getDisplayStatus(status);
            switch (s) {
                case "PENDING": return "border-yellow-200 text-yellow-600";
                case "TO SHIP": return "border-blue-200 text-blue-600";
                case "TO RECEIVE": return "border-blue-200 text-blue-600";
                case "DELIVERED": return "border-green-200 text-green-600";
                case "RECEIVED BY BUYER": return "border-green-400 text-green-700";
                case "COMPLETED": return "border-green-400 text-green-700";
                case "CANCELLED": return "border-red-200 text-red-500";
                default: return "border-gray-200 text-gray-500";
            }
        },
        getParsedAddress(addr) {
            if (!addr) return {};
            if (typeof addr === 'string') { try { return JSON.parse(addr); } catch (e) { return {}; } }
            return addr;
        },
        formatAddress(a) {
            if (!a) return '';
            return [a.houseNo, a.street, a.barangay, a.city, a.province, a.postalCode].filter(p => p).join(', ');
        }
    }
}
</script>
@endonce
