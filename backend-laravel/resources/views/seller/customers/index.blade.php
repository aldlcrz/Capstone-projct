@extends('layouts.seller')

@section('content')
<div class="space-y-4 sm:space-y-6 max-w-5xl pb-28 lg:pb-12 px-2 sm:px-6" x-data="{ search: '', modalOpen: false, customer: null, viewTab: 'info' }">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 sm:gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">✦ Client Relations</span>
                <span class="text-xs" style="color: #E8DECB;">•</span>
                <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">Patron Directory</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold tracking-tight" style="color: #1E1915;">
                Client <span class="italic font-normal" style="color: #766C60;">Directory</span>
            </h1>
            <p class="text-xs font-medium mt-1" style="color: #766C60;">View customer purchasing history, direct communication channels, and total patronage.</p>
        </div>
        
        {{-- Search Input --}}
        <div class="relative w-full sm:w-72">
            <input type="text" x-model="search" placeholder="Search by name or email..." class="w-full h-10 sm:h-11 pl-9 pr-4 rounded-xl text-xs font-semibold shadow-xs outline-none transition-all" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;" onfocus="this.style.borderColor='#C49520'; this.style.background='#FFF';" onblur="this.style.borderColor='#E8DECB'; this.style.background='#FDF8EE';">
            <svg class="w-4 h-4 absolute left-3 top-3 sm:top-3.5" style="color: #766C60;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    {{-- Customer Statistics Summary Cards --}}
    <div class="grid grid-cols-3 gap-2 sm:gap-4">
        <div class="rounded-2xl sm:rounded-3xl p-3 sm:p-5 shadow-xs space-y-0.5 sm:space-y-1 text-center sm:text-left" style="background: #FFFCF7; border: 1px solid #E8DECB;">
            <div class="text-[8px] sm:text-[10px] font-bold uppercase tracking-wider truncate" style="color: #766C60;">Unique Patrons</div>
            <div class="text-base sm:text-2xl font-black font-serif" style="color: #1E1915;">{{ count($customerList) }}</div>
            <div class="text-[8px] sm:text-[10px] hidden sm:block font-medium" style="color: #A09585;">Registered buyers</div>
        </div>

        <div class="rounded-2xl sm:rounded-3xl p-3 sm:p-5 shadow-xs space-y-0.5 sm:space-y-1 text-center sm:text-left" style="background: #FFFCF7; border: 1px solid #E8DECB;">
            <div class="text-[8px] sm:text-[10px] font-bold uppercase tracking-wider truncate" style="color: #766C60;">Orders Placed</div>
            <div class="text-base sm:text-2xl font-black font-serif" style="color: #1E1915;">{{ $customerList->sum('ordersCount') }}</div>
            <div class="text-[8px] sm:text-[10px] hidden sm:block font-medium" style="color: #A09585;">Completed transactions</div>
        </div>

        <div class="rounded-2xl sm:rounded-3xl p-3 sm:p-5 shadow-xs space-y-0.5 sm:space-y-1 text-center sm:text-left" style="background: #FFFCF7; border: 1px solid #E8DECB;">
            <div class="text-[8px] sm:text-[10px] font-bold uppercase tracking-wider truncate" style="color: #766C60;">Total Spend</div>
            <div class="text-base sm:text-2xl font-black font-serif truncate" style="color: #C49520;">₱{{ number_format($customerList->sum('totalSpent')) }}</div>
            <div class="text-[8px] sm:text-[10px] hidden sm:block font-medium" style="color: #A09585;">Lifetime value</div>
        </div>
    </div>

    {{-- Customer Capsule List --}}
    <div class="space-y-2.5 sm:space-y-3">
        @forelse($customerList as $cust)
            <div x-show="!search || '{{ strtolower($cust['name']) }}'.includes(search.toLowerCase()) || '{{ strtolower($cust['email']) }}'.includes(search.toLowerCase())"
                 @click="customer = {{ json_encode($cust) }}; viewTab = 'info'; modalOpen = true"
                 class="group rounded-2xl p-2.5 sm:p-3.5 px-4 sm:px-6 shadow-xs hover:shadow-md transition-all duration-300 cursor-pointer flex items-center justify-between gap-3 active:scale-[0.99]"
                 style="background: #FFFCF7; border: 1px solid #E8DECB;"
                 onmouseover="this.style.borderColor='#C49520';"
                 onmouseout="this.style.borderColor='#E8DECB';">
                
                {{-- Left: Avatar & Info --}}
                <div class="flex items-center gap-3 min-w-0 flex-1">
                    <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl flex items-center justify-center font-bold text-xs sm:text-sm shadow-xs shrink-0 overflow-hidden group-hover:scale-105 transition-transform" style="background: #1E1915; color: #C49520; border: 1px solid rgba(196,149,32,0.4);">
                        @if($cust['avatar'])
                            <img src="{{ str_starts_with($cust['avatar'], 'http') || str_starts_with($cust['avatar'], '/') ? $cust['avatar'] : asset('storage/' . $cust['avatar']) }}" class="w-full h-full object-cover" onerror="this.style.display='none'">
                        @else
                            {{ strtoupper(substr($cust['name'], 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="font-serif text-xs sm:text-sm font-bold truncate tracking-tight transition-colors" style="color: #1E1915;">{{ $cust['name'] }}</h3>
                            <span class="px-2 py-0.5 text-[8px] sm:text-[9px] font-bold rounded-full uppercase tracking-wider shrink-0" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;">{{ $cust['ordersCount'] }} {{ $cust['ordersCount'] === 1 ? 'Order' : 'Orders' }}</span>
                        </div>
                        <p class="text-[10px] sm:text-[11px] truncate font-medium" style="color: #766C60;">{{ $cust['email'] }}</p>
                    </div>
                </div>

                {{-- Right: Spend & Arrow --}}
                <div class="flex items-center gap-3 shrink-0">
                    <div class="text-right">
                        <div class="text-[8px] sm:text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Total Spend</div>
                        <div class="text-xs sm:text-sm font-black font-serif" style="color: #C49520;">₱{{ number_format($cust['totalSpent'], 2) }}</div>
                    </div>
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center transition-all shrink-0" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;" onmouseover="this.style.background='#C49520'; this.style.color='#FFF'; this.style.borderColor='#C49520';" onmouseout="this.style.background='#FDF8EE'; this.style.color='#766C60'; this.style.borderColor='#E8DECB';">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-3xl p-10 text-center space-y-2" style="background: #FFFCF7; border: 1px solid #E8DECB;">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center mx-auto text-xl" style="background: #FDF8EE; color: #C49520; border: 1px solid #E8DECB;">👥</div>
                <h3 class="font-serif text-sm font-bold uppercase tracking-wider" style="color: #1E1915;">No Clients Recorded</h3>
                <p class="text-[11px]" style="color: #766C60;">When buyers commission products from your shop, their client profile will be catalogued here.</p>
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
         class="fixed inset-0 bg-black/60 backdrop-blur-xs z-50 flex items-end sm:items-center justify-center p-0 sm:p-4">
        
        <div @click.away="modalOpen = false" 
             class="w-full sm:max-w-lg rounded-t-3xl sm:rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]" style="background: #FFFCF7; border: 1px solid #E8DECB;">
            
            {{-- Modal Header --}}
            <div class="relative p-6 text-center shrink-0" style="background: #1E1915; border-bottom: 1px solid rgba(255,255,255,0.08);">
                <button @click="modalOpen = false" class="absolute top-4 right-4 w-8 h-8 rounded-xl flex items-center justify-center transition-all cursor-pointer" style="background: rgba(255,255,255,0.08); color: #FFFCF7;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                
                {{-- Single Avatar & Customer Header --}}
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center font-bold text-xl shadow-lg mx-auto mb-2.5 overflow-hidden" style="background: #2E2620; color: #C49520; border: 2px solid rgba(196,149,32,0.4);">
                    <template x-if="customer && customer.avatar">
                        <img :src="customer.avatar.startsWith('http') || customer.avatar.startsWith('/') ? customer.avatar : '/storage/' + customer.avatar" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!customer || !customer.avatar">
                        <span x-text="customer ? customer.name.charAt(0).toUpperCase() : 'C'"></span>
                    </template>
                </div>
                <h2 class="font-serif text-lg font-bold" style="color: #FFFCF7;" x-text="customer ? customer.name : 'Client Details'"></h2>
                <p class="text-xs font-medium mt-0.5" style="color: rgba(255,252,247,0.6);" x-text="customer ? customer.email : ''"></p>
                <div class="mt-2 inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-widest" style="background: rgba(196,149,32,0.15); color: #C49520; border: 1px solid rgba(196,149,32,0.3);">
                    <span x-text="viewTab === 'orders' ? 'Transaction History (' + (customer ? customer.ordersCount : 0) + ')' : 'Verified Patron ✦'"></span>
                </div>
            </div>

            {{-- Modal Body --}}
            <div class="p-5 overflow-y-auto flex-1 space-y-4">
                {{-- Info View --}}
                <div x-show="viewTab === 'info'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3.5 rounded-2xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Total Orders</div>
                            <div class="text-lg font-black font-serif mt-0.5" style="color: #1E1915;" x-text="customer ? customer.ordersCount : 0"></div>
                        </div>
                        <div class="p-3.5 rounded-2xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <div class="text-[9px] font-bold uppercase tracking-widest" style="color: #766C60;">Total Spend</div>
                            <div class="text-lg font-black font-serif mt-0.5" style="color: #C49520;" x-text="customer ? '₱' + Number(customer.totalSpent).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '₱0.00'"></div>
                        </div>
                    </div>

                    <div class="space-y-2 pt-1">
                        <div class="p-3.5 rounded-2xl flex items-center justify-between" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <span class="text-xs font-bold uppercase tracking-wider" style="color: #766C60;">Mobile Number</span>
                            <span class="text-xs font-bold" style="color: #1E1915;" x-text="customer && customer.phone ? customer.phone : 'N/A'"></span>
                        </div>

                        <div class="p-3.5 rounded-2xl flex items-center justify-between" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                            <span class="text-xs font-bold uppercase tracking-wider" style="color: #766C60;">Avg. Spend Per Order</span>
                            <span class="text-xs font-black font-serif" style="color: #4A6741;" x-text="customer && customer.ordersCount > 0 ? '₱' + (customer.totalSpent / customer.ordersCount).toFixed(2) : '₱0.00'"></span>
                        </div>
                    </div>
                </div>

                {{-- Order History View --}}
                <div x-show="viewTab === 'orders'" style="display: none;" class="space-y-3">
                    <template x-if="customer && customer.history && customer.history.length > 0">
                        <div class="space-y-3">
                            <template x-for="ord in customer.history" :key="ord.id">
                                <div class="p-4 rounded-2xl space-y-2.5" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs font-bold uppercase font-serif" style="color: #1E1915;" x-text="ord.orderNumber"></div>
                                            <div class="text-[10px] font-medium" style="color: #766C60;" x-text="ord.date"></div>
                                        </div>
                                        <span :class="{
                                            'bg-green-100 text-green-700': ord.status === 'completed' || ord.status === 'delivered',
                                            'bg-amber-100 text-amber-700': ord.status === 'pending' || ord.status === 'processing',
                                            'bg-blue-100 text-blue-700': ord.status === 'shipped',
                                            'bg-red-100 text-red-700': ord.status === 'cancelled'
                                        }" class="px-2.5 py-0.5 rounded-full text-[8px] font-black uppercase tracking-widest" x-text="ord.status"></span>
                                    </div>

                                    {{-- Order Items List --}}
                                    <div class="py-2 border-t border-b space-y-1" style="border-color: #E8DECB;">
                                        <template x-for="item in ord.items" :key="item.name">
                                            <div class="flex items-center justify-between text-xs">
                                                <span class="font-medium" style="color: #1E1915;" x-text="item.name + ' × ' + item.quantity"></span>
                                                <span class="font-bold font-serif" style="color: #766C60;" x-text="'₱' + (item.price * item.quantity).toFixed(2)"></span>
                                            </div>
                                        </template>
                                    </div>

                                    <div class="flex items-center justify-between pt-0.5">
                                        <span class="text-[10px] font-bold uppercase tracking-widest" style="color: #766C60;" x-text="'Payment: ' + ord.paymentMethod"></span>
                                        <span class="text-sm font-black font-serif" style="color: #C49520;" x-text="'Total: ₱' + Number(ord.totalAmount).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="!customer || !customer.history || customer.history.length === 0">
                        <div class="p-6 text-center text-xs italic" style="color: #A09585;">No purchase records found for this patron.</div>
                    </template>
                </div>
            </div>

            {{-- Modal Footer Actions --}}
            <div class="p-4 sm:p-5 border-t shrink-0 flex items-center gap-3" style="border-color: #E8DECB;">
                <template x-if="viewTab === 'info'">
                    <button @click="viewTab = 'orders'" class="flex-1 py-2.5 text-white rounded-xl text-xs font-bold uppercase tracking-widest text-center transition-all shadow-xs cursor-pointer" style="background: #1E1915;" onmouseover="this.style.background='#C49520';" onmouseout="this.style.background='#1E1915';">
                        View Purchase History
                    </button>
                </template>
                <template x-if="viewTab === 'orders'">
                    <button @click="viewTab = 'info'" class="flex-1 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest text-center transition-all cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;">
                        ← Client Details
                    </button>
                </template>
                <a :href="'{{ route('seller.messages') }}' + (customer ? '?userId=' + customer.id + '&name=' + encodeURIComponent(customer.name) : '')" class="px-4 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest transition-all cursor-pointer" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #1E1915;" onmouseover="this.style.borderColor='#C49520'; this.style.color='#C49520';" onmouseout="this.style.borderColor='#E8DECB'; this.style.color='#1E1915';">
                    Message
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
