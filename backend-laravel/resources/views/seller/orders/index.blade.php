@extends('layouts.seller')

@section('content')
<div class="space-y-8" x-data="{
    orders: {{ $orders->toJson() }},
    searchTerm: '',
    statusFilter: 'all',
    activeOrder: null,
    statusModal: false,
    newStatus: '',

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
        const m = {
            'pending': 'bg-yellow-50 text-yellow-700 border-yellow-200',
            'processing': 'bg-blue-50 text-blue-700 border-blue-200',
            'to ship': 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'to receive': 'bg-purple-50 text-purple-700 border-purple-200',
            'shipped': 'bg-purple-50 text-purple-700 border-purple-200',
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
            const res = await fetch('/api/seller/orders/' + this.activeOrder.id + '/status', {
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
        @foreach(['all' => 'All', 'pending' => 'Pending', 'to ship' => 'To Ship', 'to receive' => 'To Receive', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $val => $label)
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
                            <button @click="openStatus(order)"
                                class="px-5 py-2.5 bg-black text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-[#C0420A] transition-all whitespace-nowrap">
                                Update Status
                            </button>
                        </div>
                    </div>

                    {{-- Items mini list --}}
                    <template x-if="order.items && order.items.length > 0">
                        <div class="mt-4 ml-14 flex gap-3 overflow-x-auto no-scrollbar">
                            <template x-for="item in order.items" :key="item.id">
                                <div class="shrink-0 bg-gray-50 rounded-lg px-3 py-2 text-[10px] font-bold text-gray-600">
                                    <span x-text="item.product?.name || 'Product'"></span>
                                    <span class="text-gray-400 ml-1" x-text="'x' + item.quantity"></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>
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
                @foreach(['Pending', 'Processing', 'To Ship', 'To Receive', 'Completed', 'Cancelled'] as $s)
                    <label class="flex items-center gap-3 p-4 rounded-xl border cursor-pointer transition-all"
                        :class="newStatus === '{{ $s }}' ? 'border-[#C0420A] bg-red-50' : 'border-gray-100 hover:border-gray-300'">
                        <input type="radio" x-model="newStatus" value="{{ $s }}" class="accent-[#C0420A]">
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
                    class="flex-1 py-3 rounded-xl bg-black text-white text-[10px] font-bold uppercase tracking-widest hover:bg-[#C0420A] transition-all">
                    Save Status
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
