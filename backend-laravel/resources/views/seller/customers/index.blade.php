@extends('layouts.seller')

@section('content')
<div class="space-y-4 sm:space-y-6 max-w-5xl pb-28 lg:pb-12 px-2 sm:px-6" x-data="{ search: '', modalOpen: false, customer: null, viewTab: 'info' }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
        <div>
            <div class="text-[9px] sm:text-[10px] font-bold text-[#C0420A] uppercase tracking-[0.2em] mb-0.5">CRM & Analytics</div>
            <h1 class="font-serif text-xl sm:text-3xl font-bold text-black uppercase">Customer <span class="text-[#C0420A] italic lowercase">Directory</span></h1>
            <p class="text-[11px] sm:text-xs text-gray-500 mt-0.5">Tap any customer capsule to view complete profile and order details.</p>
        </div>
        
        {{-- Search Input --}}
        <div class="relative w-full sm:w-72">
            <input type="text" x-model="search" placeholder="Search by name or email..." class="w-full h-10 sm:h-11 pl-9 sm:pl-10 pr-4 bg-white border border-gray-200 rounded-full text-xs font-semibold shadow-sm outline-none focus:border-[#C0420A] transition-all">
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3 sm:top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    {{-- Customer Statistics Summary Cards (3 Columns on Mobile) --}}
    <div class="grid grid-cols-3 gap-2 sm:gap-4">
        <div class="bg-white rounded-2xl sm:rounded-3xl p-3 sm:p-5 border border-gray-100 shadow-sm space-y-0.5 sm:space-y-1 text-center sm:text-left">
            <div class="text-[8px] sm:text-[10px] font-bold uppercase tracking-wider text-gray-400 truncate">Unique Buyers</div>
            <div class="text-base sm:text-3xl font-black text-black">{{ count($customerList) }}</div>
            <div class="text-[8px] sm:text-[10px] text-gray-400 hidden sm:block">Active customer base</div>
        </div>

        <div class="bg-white rounded-2xl sm:rounded-3xl p-3 sm:p-5 border border-gray-100 shadow-sm space-y-0.5 sm:space-y-1 text-center sm:text-left">
            <div class="text-[8px] sm:text-[10px] font-bold uppercase tracking-wider text-gray-400 truncate">Orders Placed</div>
            <div class="text-base sm:text-3xl font-black text-[#C0420A]">{{ $customerList->sum('ordersCount') }}</div>
            <div class="text-[8px] sm:text-[10px] text-gray-400 hidden sm:block">Cumulative order volume</div>
        </div>

        <div class="bg-white rounded-2xl sm:rounded-3xl p-3 sm:p-5 border border-gray-100 shadow-sm space-y-0.5 sm:space-y-1 text-center sm:text-left">
            <div class="text-[8px] sm:text-[10px] font-bold uppercase tracking-wider text-gray-400 truncate">Total Revenue</div>
            <div class="text-base sm:text-3xl font-black text-emerald-600 truncate">₱{{ number_format($customerList->sum('totalSpent')) }}</div>
            <div class="text-[8px] sm:text-[10px] text-gray-400 hidden sm:block">Lifetime purchases</div>
        </div>
    </div>

    {{-- Customer Capsule List --}}
    <div class="space-y-2.5 sm:space-y-3">
        @forelse($customerList as $cust)
            <div x-show="!search || '{{ strtolower($cust['name']) }}'.includes(search.toLowerCase()) || '{{ strtolower($cust['email']) }}'.includes(search.toLowerCase())"
                 @click="customer = {{ json_encode($cust) }}; viewTab = 'info'; modalOpen = true"
                 class="group bg-white hover:bg-gray-50/80 rounded-full p-2.5 sm:p-3.5 px-4 sm:px-6 border border-gray-100 hover:border-[#C0420A]/40 shadow-sm hover:shadow-md transition-all duration-300 cursor-pointer flex items-center justify-between gap-3 active:scale-[0.99]">
                
                {{-- Left: Avatar & Info --}}
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gradient-to-tr from-[#3D2B1F] to-[#C0420A] flex items-center justify-center text-white font-black text-xs sm:text-base shadow-sm shrink-0 overflow-hidden group-hover:scale-105 transition-transform">
                        @if($cust['avatar'])
                            <img src="{{ str_starts_with($cust['avatar'], 'http') || str_starts_with($cust['avatar'], '/') ? $cust['avatar'] : asset('storage/' . $cust['avatar']) }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                        @else
                            {{ strtoupper(substr($cust['name'], 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs sm:text-sm font-black text-black truncate uppercase tracking-tight group-hover:text-[#C0420A] transition-colors">{{ $cust['name'] }}</h3>
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-[8px] sm:text-[9px] font-bold rounded-full uppercase tracking-wider shrink-0">{{ $cust['ordersCount'] }} {{ $cust['ordersCount'] === 1 ? 'Order' : 'Orders' }}</span>
                        </div>
                        <p class="text-[10px] sm:text-[11px] text-gray-400 truncate font-medium">{{ $cust['email'] }}</p>
                    </div>
                </div>

                {{-- Right: Spend & Arrow --}}
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <div class="text-[8px] sm:text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Spent</div>
                        <div class="text-xs sm:text-sm font-black text-[#C0420A]">₱{{ number_format($cust['totalSpent'], 2) }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-gray-100 group-hover:bg-[#C0420A] group-hover:text-white flex items-center justify-center text-gray-400 transition-all shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl p-10 text-center space-y-2 border border-gray-100">
                <div class="w-12 h-12 rounded-full bg-gray-100 text-gray-400 flex items-center justify-center mx-auto text-xl">👥</div>
                <h3 class="text-xs sm:text-sm font-black text-black uppercase tracking-wider">No Customers Found</h3>
                <p class="text-[11px] text-gray-400">When buyers purchase products from your store, their contact and order records will appear here.</p>
            </div>
        @endforelse
    </div>

    {{-- Customer Detail Modal --}}
    <div x-show="modalOpen" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        
        <div @click.away="modalOpen = false" 
             class="w-full sm:max-w-lg bg-white rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            {{-- Modal Header --}}
            <div class="relative bg-gradient-to-br from-[#2A2A28] to-black p-6 text-white text-center shrink-0">
                <button @click="modalOpen = false" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                
                {{-- Single Avatar & Customer Header --}}
                <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-[#3D2B1F] to-[#C0420A] flex items-center justify-center text-white font-black text-2xl shadow-lg border-2 border-white/20 mx-auto mb-3 overflow-hidden">
                    <template x-if="customer &amp;&amp; customer.avatar">
                        <img :src="customer.avatar.startsWith('http') || customer.avatar.startsWith('/') ? customer.avatar : '/storage/' + customer.avatar" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!customer || !customer.avatar">
                        <span x-text="customer ? customer.name.charAt(0).toUpperCase() : 'C'"></span>
                    </template>
                </div>
                <h2 class="text-base font-black uppercase tracking-tight" x-text="customer ? customer.name : 'Customer Details'"></h2>
                <p class="text-xs text-gray-300 font-medium mt-0.5" x-text="customer ? customer.email : ''"></p>
                <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-1 bg-white/10 rounded-full text-[9px] font-bold uppercase tracking-widest text-amber-300">
                    <span x-text="viewTab === 'orders' ? 'Purchase History (' + (customer ? customer.ordersCount : 0) + ')' : 'Verified Buyer ✓'"></span>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="p-5 overflow-y-auto flex-1 space-y-4">
                {{-- Info View --}}
                <div x-show="viewTab === 'info'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3.5 bg-gray-50 rounded-2xl border border-gray-100">
                            <div class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Total Orders</div>
                            <div class="text-lg font-black text-black mt-0.5" x-text="customer ? customer.ordersCount : 0"></div>
                        </div>
                        <div class="p-3.5 bg-amber-50/50 rounded-2xl border border-amber-100/50">
                            <div class="text-[9px] font-bold text-amber-700 uppercase tracking-widest">Total Spent</div>
                            <div class="text-lg font-black text-[#C0420A] mt-0.5" x-text="customer ? '₱' + Number(customer.totalSpent).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '₱0.00'"></div>
                        </div>
                    </div>

                    <div class="space-y-2 pt-1">
                        <div class="p-3.5 bg-gray-50 rounded-2xl flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Mobile Number</span>
                            <span class="text-xs font-black text-black" x-text="customer &amp;&amp; customer.phone ? customer.phone : 'N/A'"></span>
                        </div>

                        <div class="p-3.5 bg-gray-50 rounded-2xl flex items-center justify-between">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Avg. Spend Per Order</span>
                            <span class="text-xs font-black text-emerald-600" x-text="customer &amp;&amp; customer.ordersCount > 0 ? '₱' + (customer.totalSpent / customer.ordersCount).toFixed(2) : '₱0.00'"></span>
                        </div>
                    </div>
                </div>

                {{-- Order History View --}}
                <div x-show="viewTab === 'orders'" style="display: none;" class="space-y-3">
                    <template x-if="customer &amp;&amp; customer.history &amp;&amp; customer.history.length > 0">
                        <div class="space-y-3">
                            <template x-for="ord in customer.history" :key="ord.id">
                                <div class="p-4 bg-gray-50/80 border border-gray-100 rounded-2xl space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-black text-black uppercase" x-text="ord.orderNumber"></div>
                                            <div class="text-[10px] text-gray-400 font-medium" x-text="ord.date"></div>
                                        </div>
                                        <span :class="{
                                            'bg-green-100 text-green-700': ord.status === 'completed' || ord.status === 'delivered',
                                            'bg-amber-100 text-amber-700': ord.status === 'pending' || ord.status === 'processing',
                                            'bg-blue-100 text-blue-700': ord.status === 'shipped',
                                            'bg-red-100 text-red-700': ord.status === 'cancelled'
                                        }" class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest" x-text="ord.status"></span>
                                    </div>

                                    {{-- Order Items List --}}
                                    <div class="py-2 border-t border-b border-gray-100 space-y-1">
                                        <template x-for="item in ord.items" :key="item.name">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-bold text-gray-800" x-text="item.name + ' × ' + item.quantity"></span>
                                                <span class="font-semibold text-gray-600" x-text="'₱' + (item.price * item.quantity).toFixed(2)"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex items-center justify-between pt-0.5">
                                        <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest" x-text="'Payment: ' + ord.paymentMethod"></span>
                                        <span class="text-sm font-black text-[#C0420A]" x-text="'Total: ₱' + Number(ord.totalAmount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!customer || !customer.history || customer.history.length === 0">
                        <div class="p-6 text-center text-xs text-gray-400 italic">No purchase history records found for this customer.</div>
                    </template>
                </div>
            </div>

            {{-- Modal Footer Actions --}}
            <div class="p-5 border-t border-gray-100 shrink-0 flex items-center gap-3">
                <template x-if="viewTab === 'info'">
                    <button @click="viewTab = 'orders'" class="flex-1 py-3.5 bg-black text-white rounded-2xl text-xs font-black uppercase tracking-widest text-center hover:bg-[#C0420A] transition-all shadow-md">
                        View Order History
                    </button>
                </template>
                <template x-if="viewTab === 'orders'">
                    <button @click="viewTab = 'info'" class="flex-1 py-3.5 bg-gray-100 text-gray-800 rounded-2xl text-xs font-black uppercase tracking-widest text-center hover:bg-gray-200 transition-all">
                        ← Customer Info
                    </button>
                </template>
                <a :href="'{{ route('seller.messages') }}' + (customer ? '?userId=' + customer.id + '&name=' + encodeURIComponent(customer.name) : '')" class="px-5 py-3.5 bg-gray-100 text-gray-700 rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-gray-200 transition-all">
                    Message
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
