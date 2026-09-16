@extends('layouts.superadmin')

@section('content')
<div x-data="commissionsPage()" class="space-y-8">
    <!-- Header & Period Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="inline-flex items-center gap-2">
                <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Financial Oversight</span>
                <span class="text-gray-300 text-xs">·</span>
                <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Commission Ledger</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-gray-900 leading-tight mt-0.5">
                Commission &amp; <span class="text-[#C0420A] font-light italic">Shop Sales</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Track platform gross merchandise value, commission balances, and seller payouts</p>
        </div>

        <form method="GET" action="{{ route('superadmin.commissions') }}" class="flex items-center gap-2 bg-white p-2 rounded-2xl border border-gray-100 shadow-xs">
            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider pl-2">Period:</span>
            <select name="period" onchange="this.form.submit()"
                    class="px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-800 text-xs font-bold focus:outline-none focus:border-[#C0422A] cursor-pointer">
                @foreach($periods as $p)
                    <option value="{{ $p }}" {{ $period === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
                @if(!$periods->contains($period))
                    <option value="{{ $period }}" selected>{{ $period }} (Current)</option>
                @endif
            </select>
        </form>
    </div>

    <!-- Summary KPI Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Gross Sales</span>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Period Total Sales</div>
                <div class="text-2xl font-black text-gray-900 mt-0.5">₱{{ number_format($periodTotalSales, 2) }}</div>
                <div class="text-[11px] text-gray-400 mt-1 font-medium">{{ $period }} billing cycle</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-red-50 text-[#C0422A] flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-[9px] font-bold text-[#C0422A] uppercase tracking-wider">{{ $rate }}% Target</span>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Commission Due</div>
                <div class="text-2xl font-black text-[#C0422A] mt-0.5">₱{{ number_format($periodTotalDue, 2) }}</div>
                <div class="text-[11px] text-gray-400 mt-1 font-medium">Expected revenue</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <span class="text-[9px] font-bold text-emerald-600 uppercase tracking-wider">Collected</span>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Total Realized</div>
                <div class="text-2xl font-black text-emerald-600 mt-0.5">₱{{ number_format($periodTotalPaid, 2) }}</div>
                <div class="text-[11px] text-gray-400 mt-1 font-medium">Settled commissions</div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <span class="text-[9px] font-bold text-rose-600 uppercase tracking-wider">Outstanding</span>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Unpaid Sellers</div>
                <div class="text-2xl font-black text-rose-600 mt-0.5">{{ $periodUnpaid }} <span class="text-xs text-gray-400 font-normal">Shops</span></div>
                <div class="text-[11px] text-gray-400 mt-1 font-medium">Pending payment clearance</div>
            </div>
        </div>
    </div>

    <!-- Global Rate Config -->
    <div class="bg-white border border-gray-100 rounded-2xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
        <div class="space-y-1">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider flex items-center gap-2">
                <span>⚙️ Global Commission Rate Configuration</span>
            </h3>
            <p class="text-xs text-gray-500">Default rate is <strong class="text-gray-800 font-bold">5%</strong> of product price per completed order across all registered shops.</p>
        </div>

        <form action="{{ route('superadmin.commission-rate') }}" method="POST" class="flex items-center gap-2">
            @csrf
            <div class="relative">
                <input type="number" step="0.1" min="0" max="100" name="rate" value="{{ $rate }}" required
                       class="w-24 px-3.5 py-2 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-xs font-bold text-right pr-7 focus:outline-none focus:border-[#C0422A] transition-all">
                <span class="absolute right-2.5 top-2.5 text-xs text-gray-400 font-bold">%</span>
            </div>
            <button type="submit" class="px-4 py-2.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white rounded-xl text-[10px] font-bold uppercase tracking-widest transition-all cursor-pointer shadow-xs">
                Save Rate
            </button>
        </form>
    </div>

    <!-- Currently Frozen Shops Section (if any) -->
    @if($frozenSellers->count() > 0)
    <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-6 space-y-4 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <h3 class="text-xs font-bold text-blue-800 uppercase tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                ❄️ Currently Frozen Shops ({{ $frozenSellers->count() }})
            </h3>
            <span class="text-[11px] text-blue-600 font-medium">Products remain visible to buyers, but purchases &amp; seller dashboard access are locked.</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($frozenSellers as $fs)
            @php
                $sellerObj = $fs['seller'];
                $shopName  = addslashes($sellerObj->shopName ?: $sellerObj->name);
                $email     = addslashes($sellerObj->email);
                $reason    = addslashes($fs['reason'] ?? '');
                $pm        = addslashes($fs['paymentMethod'] ?? '');
                $ref       = addslashes($fs['referenceNumber'] ?? '');
                $proof     = addslashes($fs['paymentProof'] ?? '');
            @endphp
            <div class="bg-white border border-blue-200/80 rounded-xl p-4 flex items-center justify-between shadow-xs">
                <div>
                    <div class="text-sm font-bold text-gray-900 flex items-center gap-2">
                        {{ $sellerObj->shopName ?: $sellerObj->name }}
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-700 border border-blue-200 text-[9px] font-bold rounded-full uppercase">Frozen</span>
                    </div>
                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $sellerObj->email }}</div>
                    @if($fs['reason'])
                        <div class="text-[10px] text-gray-500 mt-1 italic">Reason: {{ $fs['reason'] }}</div>
                    @endif
                </div>

                <button type="button"
                        @click="openUnfreezeModal('{{ $sellerObj->id }}', '{{ $shopName }}', '{{ $email }}', '{{ $reason }}', '{{ $pm }}', '{{ $ref }}', '{{ $proof }}')"
                        class="px-3.5 py-2 bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-600 hover:text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                    🔓 Unfreeze
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- All Shops Commission Table -->
    <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm">
        <div class="px-6 py-4.5 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
            <div>
                <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Per-Shop Sales &amp; Commission Ledger ({{ $period }})</h3>
                <p class="text-[11px] text-gray-400 mt-0.5 font-medium">Complete breakdown of gross sales, calculated platform fees, and payment status.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F8F7F4] border-b border-gray-100 text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                        <th class="px-6 py-3.5">Shop &amp; Artisan</th>
                        <th class="px-6 py-3.5">Period Sales</th>
                        <th class="px-6 py-3.5">Platform Fee ({{ $rate }}%)</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Payment Verification</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($sellers as $s)
                    @php
                        $isFrozen  = $s['seller']->status === 'frozen';
                        $shopName  = addslashes($s['seller']->shopName ?: $s['seller']->name);
                        $email     = addslashes($s['seller']->email);
                        $amountStr = number_format($s['commissionAmount'], 2);
                        $pm        = addslashes($s['paymentMethod'] ?? '');
                        $ref       = addslashes($s['referenceNumber'] ?? '');
                        $proof     = addslashes($s['paymentProof'] ?? '');
                    @endphp
                    <tr class="hover:bg-gray-50/80 transition-colors {{ $isFrozen ? 'bg-blue-50/40' : '' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3 cursor-pointer group" @click="openStatementModal(@js($s))" title="Click to view full balance and ledger statement">
                                <div class="w-10 h-10 rounded-xl {{ $isFrozen ? 'bg-blue-100 text-blue-600 border border-blue-200' : 'bg-black text-white' }} flex items-center justify-center font-bold text-sm shrink-0 group-hover:scale-105 group-hover:bg-[#C0420A] group-hover:text-white transition-all shadow-xs">
                                    {{ strtoupper(substr($s['seller']->shopName ?: $s['seller']->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-gray-900 group-hover:text-[#C0420A] transition-colors flex items-center gap-2">
                                        <span class="truncate">{{ $s['seller']->shopName ?: $s['seller']->name }}</span>
                                        @if($isFrozen)
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-700 border border-blue-200 text-[8px] font-bold rounded-full uppercase shrink-0">Frozen</span>
                                        @endif
                                        <svg class="w-3.5 h-3.5 text-gray-400 group-hover:text-[#C0420A] group-hover:translate-x-0.5 transition-all shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </div>
                                    <div class="text-[10px] text-gray-400 truncate">{{ $s['seller']->name }} • {{ $s['seller']->email }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 font-bold text-gray-900 font-mono text-sm">
                            ₱{{ number_format($s['totalSales'], 2) }}
                        </td>

                        <td class="px-6 py-4 font-bold text-[#C0422A] font-mono text-sm">
                            ₱{{ number_format($s['commissionAmount'], 2) }}
                        </td>

                        <td class="px-6 py-4">
                            @if($s['status'] === 'paid')
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full font-bold uppercase text-[9px]">✓ Paid</span>
                            @else
                                <span class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-full font-bold uppercase text-[9px]">Unpaid</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-gray-500">
                            @if($s['status'] === 'paid')
                                <div class="text-[10px]">
                                    <span class="text-emerald-700 font-bold">Settled</span>
                                    @if($s['paidAt'])
                                        <div class="text-gray-400">{{ \Carbon\Carbon::parse($s['paidAt'])->format('M d, Y') }}</div>
                                    @endif
                                </div>
                            @else
                                <div class="text-[10px] space-y-1">
                                    @if($s['referenceNumber'] || $s['paymentProof'])
                                        <div class="text-blue-700 font-bold flex items-center gap-1">
                                            <span>💳 {{ $s['paymentMethod'] ?? 'Payment' }}</span>
                                        </div>
                                        @if($s['referenceNumber'])
                                            <div class="font-mono text-gray-900 font-bold">Ref: {{ $s['referenceNumber'] }}</div>
                                        @endif
                                        @if($s['paymentProof'])
                                            <button type="button"
                                                    @click="openProofModal('{{ asset('storage/' . ltrim($s['paymentProof'], '/')) }}', '{{ $shopName }} · Proof of Payment', '{{ $s['referenceNumber'] ?? '' }}')"
                                                    class="inline-flex items-center gap-1 text-[10px] text-[#C0422A] hover:underline font-bold cursor-pointer">
                                                🔍 View Proof
                                            </button>
                                        @endif
                                    @else
                                        <div class="text-gray-400">Due by 7th of next month</div>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Mark Paid Button -->
                                @if($s['status'] !== 'paid')
                                    <button type="button"
                                            @click="openMarkPaidModal('{{ $s['seller']->id }}', '{{ $shopName }}', '{{ $email }}', '{{ $amountStr }}', '{{ $pm }}', '{{ $ref }}', '{{ $proof }}')"
                                            class="px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-600 hover:text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        ✓ Mark Paid
                                    </button>
                                @endif

                                <!-- Freeze / Unfreeze Buttons -->
                                @if($isFrozen)
                                    <button type="button"
                                            @click="openUnfreezeModal('{{ $s['seller']->id }}', '{{ $shopName }}', '{{ $email }}', '{{ addslashes($s['seller']->violationReason ?? '') }}', '{{ $pm }}', '{{ $ref }}', '{{ $proof }}')"
                                            class="px-3 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-600 hover:text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        🔓 Unfreeze
                                    </button>
                                @else
                                    <button type="button"
                                            @click="openFreezeModal('{{ $s['seller']->id }}', '{{ $shopName }}', '{{ $amountStr }}')"
                                            class="px-3 py-1.5 bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-600 hover:text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        ❄️ Freeze
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400 italic">No verified seller accounts found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 1. Freeze Confirmation Modal -->
    <div x-show="showModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
         style="display: none;"
         x-cloak>

        <div class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl border border-gray-100 space-y-5">
            <div class="flex items-center gap-3 text-rose-600">
                <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-center font-bold text-lg shrink-0">
                    ⚠️
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Confirm Account Freeze</h3>
                    <p class="text-xs text-gray-400">Governance enforcement lock</p>
                </div>
            </div>

            <div class="text-xs text-gray-600 leading-relaxed space-y-2 bg-gray-50/80 p-4 rounded-2xl border border-gray-100">
                <p>Are you sure you want to freeze shop <strong class="text-gray-900" x-text="targetShopName"></strong>?</p>
                <div class="space-y-1 text-gray-500">
                    <div>• Current Unpaid Commission: <strong class="text-[#C0422A]">₱<span x-text="targetAmount"></span></strong></div>
                    <div>• Products remain visible, but checkout &amp; seller login are locked.</div>
                </div>
            </div>

            <form x-bind:action="freezeUrl" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <input type="hidden" name="period" value="{{ $period }}">

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1.5">Reason for Freeze</label>
                    <input type="text" name="reason" x-bind:value="'Unpaid commission for period {{ $period }} (₱' + targetAmount + ')'" required
                           class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-xs focus:outline-none focus:border-[#C0422A] transition-all">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                        Confirm Freeze
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Mark Paid Proof Confirmation Modal -->
    <div x-show="showMarkPaidModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
         style="display: none;"
         x-cloak>

        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-gray-100 space-y-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center gap-3 text-emerald-600">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center font-bold text-lg shrink-0">
                    💳
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Confirm Mark Commission as Paid</h3>
                    <p class="text-xs text-gray-400">Review seller payment verification proof before proceeding</p>
                </div>
            </div>

            <!-- Seller & Amount Summary -->
            <div class="bg-gray-50/80 border border-gray-100 rounded-2xl p-4 space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Shop / Seller:</span>
                    <span class="font-bold text-gray-900" x-text="markPaidShopName"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Seller Email:</span>
                    <span class="text-gray-600" x-text="markPaidEmail"></span>
                </div>
                <div class="flex justify-between items-center border-t border-gray-200/80 pt-2">
                    <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Period &amp; Amount Due:</span>
                    <span class="font-bold text-[#C0422A] text-sm">₱<span x-text="markPaidAmount"></span> ({{ $period }})</span>
                </div>
            </div>

            <!-- Submitted Payment Proof Details -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Submitted Payment Proof</h4>

                <template x-if="markPaidRef || markPaidProof">
                    <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest">Payment Gateway</span>
                            <span class="px-2.5 py-0.5 bg-blue-600 text-white rounded-full font-bold uppercase text-[9px]" x-text="markPaidMethod || 'GCash/Maya'"></span>
                        </div>

                        <template x-if="markPaidRef">
                            <div>
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest block">Reference / Transaction Number</span>
                                <span class="font-mono text-sm font-bold text-gray-900 bg-white px-3 py-1.5 rounded-xl border border-blue-200 block mt-1 select-all" x-text="markPaidRef"></span>
                            </div>
                        </template>

                        <template x-if="markPaidProof">
                            <div>
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest block mb-1">Screenshot Proof Image</span>
                                <button type="button" @click="openProofModal('/storage/' + markPaidProof, markPaidShopName + ' · Payment Receipt', markPaidRef)" class="block w-full group relative overflow-hidden rounded-xl border border-blue-200 bg-white cursor-pointer text-left">
                                    <img x-bind:src="'/storage/' + markPaidProof" class="w-full max-h-48 object-contain p-2 group-hover:scale-105 transition-all">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold transition-all">
                                        🔍 Click to Expand Proof Modal
                                    </div>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!markPaidRef && !markPaidProof">
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-700 flex items-center gap-2 font-medium">
                        <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span>No payment reference number or screenshot proof was uploaded by the seller for this period.</span>
                    </div>
                </template>
            </div>

            <!-- Action Form -->
            <form x-bind:action="markPaidUrl" method="POST" class="space-y-4 pt-2 border-t border-gray-100">
                @csrf @method('PATCH')
                <input type="hidden" name="period" value="{{ $period }}">

                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showMarkPaidModal = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md cursor-pointer">
                        ✓ Confirm &amp; Mark Paid
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Unfreeze Proof Confirmation Modal -->
    <div x-show="showUnfreezeModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
         style="display: none;"
         x-cloak>

        <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-gray-100 space-y-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center gap-3 text-blue-600">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center font-bold text-lg shrink-0">
                    🔓
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Confirm Account Unfreeze</h3>
                    <p class="text-xs text-gray-400">Restore seller shop access and marketplace checkout</p>
                </div>
            </div>

            <!-- Seller Summary -->
            <div class="bg-gray-50/80 border border-gray-100 rounded-2xl p-4 space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Shop / Seller:</span>
                    <span class="font-bold text-gray-900" x-text="unfreezeShopName"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Seller Email:</span>
                    <span class="text-gray-600" x-text="unfreezeEmail"></span>
                </div>
                <template x-if="unfreezeReason">
                    <div class="flex justify-between items-center border-t border-gray-200/80 pt-2">
                        <span class="text-gray-400 font-bold uppercase tracking-wider text-[10px]">Freeze Reason:</span>
                        <span class="text-blue-700 font-semibold italic text-[11px]" x-text="unfreezeReason"></span>
                    </div>
                </template>
            </div>

            <!-- Submitted Payment Proof Details -->
            <div class="space-y-3">
                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-widest">Submitted Payment Proof</h4>

                <template x-if="unfreezeRef || unfreezeProof">
                    <div class="bg-blue-50/70 border border-blue-200 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-blue-700 uppercase tracking-widest">Payment Gateway</span>
                            <span class="px-2.5 py-0.5 bg-blue-600 text-white rounded-full font-bold uppercase text-[9px]" x-text="unfreezeMethod || 'GCash/Maya'"></span>
                        </div>

                        <template x-if="unfreezeRef">
                            <div>
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest block">Reference / Transaction Number</span>
                                <span class="font-mono text-sm font-bold text-gray-900 bg-white px-3 py-1.5 rounded-xl border border-blue-200 block mt-1 select-all" x-text="unfreezeRef"></span>
                            </div>
                        </template>

                        <template x-if="unfreezeProof">
                            <div>
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest block mb-1">Screenshot Proof Image</span>
                                <button type="button" @click="openProofModal('/storage/' + unfreezeProof, unfreezeShopName + ' · Proof Receipt', unfreezeRef)" class="block w-full group relative overflow-hidden rounded-xl border border-blue-200 bg-white cursor-pointer text-left">
                                    <img x-bind:src="'/storage/' + unfreezeProof" class="w-full max-h-48 object-contain p-2 group-hover:scale-105 transition-all">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold transition-all">
                                        🔍 Click to Expand Proof Modal
                                    </div>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="!unfreezeRef && !unfreezeProof">
                    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-4 text-xs text-amber-700 flex items-center gap-2 font-medium">
                        <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span>No payment reference number or screenshot proof uploaded.</span>
                    </div>
                </template>
            </div>

            <!-- Action Form -->
            <form x-bind:action="unfreezeUrl" method="POST" class="space-y-4 pt-2 border-t border-gray-100">
                @csrf @method('PATCH')

                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showUnfreezeModal = false" class="px-4 py-2.5 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md cursor-pointer">
                        🔓 Confirm &amp; Unfreeze Account
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 4. Artisan Statement & Commission Ledger Modal -->
    <div x-show="showStatementModal"
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm overflow-y-auto"
         @click.self="showStatementModal = false">
        
        <div class="relative bg-white rounded-3xl max-w-4xl w-full p-6 sm:p-8 shadow-2xl border border-gray-100 my-8 space-y-6 max-h-[90vh] overflow-y-auto no-scrollbar"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">

            <!-- Modal Header -->
            <div class="flex items-start justify-between border-b border-gray-100 pb-5">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-[#3D2B1F] text-white flex items-center justify-center font-bold text-xl shadow-xs shrink-0">
                        <span x-text="(selectedSeller?.seller?.shopName || selectedSeller?.seller?.name || 'A').substring(0, 1).toUpperCase()"></span>
                    </div>
                    <div>
                        <div class="inline-flex items-center gap-2">
                            <span class="text-[9px] font-black uppercase tracking-[0.25em] text-[#C0422A]">Artisan Financial Statement</span>
                            <span class="text-gray-300 text-xs">·</span>
                            <span class="text-[9px] font-bold uppercase tracking-[0.2em] text-gray-400">Commission Ledger</span>
                        </div>
                        <h2 class="font-serif text-xl sm:text-2xl font-bold text-gray-900 flex items-center gap-2 mt-0.5">
                            <span x-text="selectedSeller?.seller?.shopName || selectedSeller?.seller?.name"></span>
                            <template x-if="selectedSeller?.seller?.isVerified">
                                <span class="w-5 h-5 rounded-full bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center text-[10px] font-black" title="Verified Artisan">✓</span>
                            </template>
                            <template x-if="selectedSeller?.seller?.status === 'frozen'">
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 border border-blue-200 text-[9px] font-bold rounded-full uppercase">Frozen</span>
                            </template>
                        </h2>
                        <div class="flex items-center gap-3 text-xs text-gray-400 mt-1 flex-wrap font-medium">
                            <span x-text="selectedSeller?.seller?.name"></span>
                            <span>•</span>
                            <span class="font-mono text-gray-600" x-text="selectedSeller?.seller?.email"></span>
                            <template x-if="selectedSeller?.seller?.mobileNumber">
                                <span class="font-mono text-gray-500" x-text="'• ' + selectedSeller?.seller?.mobileNumber"></span>
                            </template>
                            <template x-if="selectedSeller?.seller?.shopCity">
                                <span class="text-gray-500" x-text="'• ' + selectedSeller?.seller?.shopCity + (selectedSeller?.seller?.shopProvince ? ', ' + selectedSeller?.seller?.shopProvince : '')"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <button type="button" @click="showStatementModal = false" class="text-gray-400 hover:text-gray-700 p-2 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Key Financial Summary KPIs -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                <!-- 1. Total Outstanding Balance (Must Pay) -->
                <div :class="selectedSeller?.totalOutstandingBalance > 0 ? 'bg-rose-50/70 border-rose-200 text-rose-900 ring-2 ring-rose-500/10' : 'bg-emerald-50/60 border-emerald-200 text-emerald-900'"
                     class="rounded-2xl border p-4 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-black uppercase tracking-wider" :class="selectedSeller?.totalOutstandingBalance > 0 ? 'text-rose-600' : 'text-emerald-600'">Outstanding Balance</span>
                            <span class="w-2 h-2 rounded-full" :class="selectedSeller?.totalOutstandingBalance > 0 ? 'bg-rose-500 animate-pulse' : 'bg-emerald-500'"></span>
                        </div>
                        <div class="text-2xl font-black font-mono mt-1" :class="selectedSeller?.totalOutstandingBalance > 0 ? 'text-rose-700' : 'text-emerald-700'"
                             x-text="formatMoney(selectedSeller?.totalOutstandingBalance)"></div>
                    </div>
                    <div class="text-[10px] mt-2 font-medium" :class="selectedSeller?.totalOutstandingBalance > 0 ? 'text-rose-600' : 'text-emerald-600'">
                        <span x-text="selectedSeller?.totalOutstandingBalance > 0 ? 'Total unpaid platform fees due across all periods' : '✓ All billing periods fully settled'"></span>
                    </div>
                </div>

                <!-- 2. Current Billing Period Fee -->
                <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Period ({{ $period }}) Fee</span>
                            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                                  :class="selectedSeller?.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                  x-text="selectedSeller?.status === 'paid' ? 'Paid' : 'Unpaid'"></span>
                        </div>
                        <div class="text-xl font-black font-mono text-[#C0422A] mt-1" x-text="formatMoney(selectedSeller?.commissionAmount)"></div>
                    </div>
                    <div class="text-[10px] text-gray-400 mt-2 font-medium">
                        Period Sales: <span class="font-bold text-gray-800" x-text="formatMoney(selectedSeller?.totalSales)"></span>
                    </div>
                </div>

                <!-- 3. All-Time Lifetime Sales -->
                <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Lifetime Gross Sales</span>
                        <div class="text-xl font-black font-mono text-gray-900 mt-1" x-text="formatMoney(selectedSeller?.allTimeSales)"></div>
                    </div>
                    <div class="text-[10px] text-gray-400 mt-2 font-medium">
                        Cumulative customer order volume
                    </div>
                </div>

                <!-- 4. Lifetime Paid Commissions -->
                <div class="bg-white rounded-2xl border border-gray-200 p-4 shadow-xs flex flex-col justify-between">
                    <div>
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Commissions Settled</span>
                        <div class="text-xl font-black font-mono text-emerald-600 mt-1" x-text="formatMoney(selectedSeller?.allTimePaid)"></div>
                    </div>
                    <div class="text-[10px] text-gray-400 mt-2 font-medium">
                        Realized revenue contributed
                    </div>
                </div>
            </div>

            <!-- Current Period Payment Submission (If Reference or Proof Exists) -->
            <template x-if="selectedSeller?.referenceNumber || selectedSeller?.paymentProof">
                <div class="bg-blue-50/60 border border-blue-200 rounded-2xl p-5 space-y-3">
                    <div class="flex items-center justify-between border-b border-blue-200/80 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold text-blue-900 uppercase tracking-wider">💳 Latest Remittance Submission ({{ $period }})</span>
                            <template x-if="selectedSeller?.status === 'paid'">
                                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[9px] font-bold rounded-full uppercase">Verified &amp; Paid</span>
                            </template>
                        </div>
                        <template x-if="selectedSeller?.status !== 'paid'">
                            <button type="button"
                                    @click="openMarkPaidModal(selectedSeller?.seller?.id, selectedSeller?.seller?.shopName || selectedSeller?.seller?.name, selectedSeller?.seller?.email, selectedSeller?.commissionAmount, selectedSeller?.paymentMethod, selectedSeller?.referenceNumber, selectedSeller?.paymentProof); showStatementModal = false;"
                                    class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                                ✓ Verify &amp; Mark Paid
                            </button>
                        </template>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="space-y-2">
                            <div>
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest block">Payment Channel</span>
                                <span class="font-bold text-gray-900" x-text="selectedSeller?.paymentMethod || 'Manual Transfer'"></span>
                            </div>
                            <div>
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest block">Reference / Transaction Number</span>
                                <span class="font-mono font-bold text-blue-700 bg-white px-3 py-1 rounded-lg border border-blue-200 inline-block mt-0.5" x-text="selectedSeller?.referenceNumber || 'N/A'"></span>
                            </div>
                        </div>

                        <template x-if="selectedSeller?.paymentProof">
                            <div>
                                <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest block mb-1">Attached Receipt Proof</span>
                                <button type="button"
                                        @click="openProofModal(selectedSeller?.paymentProof, (selectedSeller?.seller?.shopName || selectedSeller?.seller?.name) + ' · Remittance Proof', selectedSeller?.referenceNumber)"
                                        class="inline-block group relative overflow-hidden rounded-xl border border-blue-200 bg-white p-1 max-w-xs shadow-xs cursor-pointer text-left">
                                    <img :src="'/storage/' + selectedSeller?.paymentProof" class="w-full max-h-32 object-contain rounded-lg group-hover:scale-105 transition-transform" alt="Proof Receipt">
                                    <div class="text-[10px] text-blue-600 font-bold text-center mt-1 group-hover:underline">🔍 Click to inspect receipt modal</div>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Complete Billing History Table -->
            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden shadow-xs space-y-0">
                <div class="px-5 py-3.5 bg-gray-50/80 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Billing &amp; Settlement History</h4>
                        <p class="text-[10px] text-gray-400 font-medium">All historical monthly commission records for this artisan</p>
                    </div>
                    <span class="text-[10px] font-bold text-gray-500 bg-white px-2.5 py-1 rounded-full border border-gray-200"
                          x-text="(selectedSeller?.history?.length || 0) + ' Records'"></span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-[#F8F7F4] border-b border-gray-100 text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                                <th class="px-5 py-3">Period</th>
                                <th class="px-5 py-3">Gross Sales</th>
                                <th class="px-5 py-3">Commission Due</th>
                                <th class="px-5 py-3">Status</th>
                                <th class="px-5 py-3">Due Date</th>
                                <th class="px-5 py-3">Settlement Reference</th>
                                <th class="px-5 py-3">Settled On</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 font-medium">
                            <template x-if="!selectedSeller?.history || selectedSeller?.history?.length === 0">
                                <tr>
                                    <td colspan="7" class="px-5 py-8 text-center text-gray-400 italic">
                                        No billing cycles recorded for this artisan yet.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="rec in (selectedSeller?.history || [])" :key="rec.id || rec.period">
                                <tr class="hover:bg-gray-50/80 transition-colors">
                                    <td class="px-5 py-3 font-bold font-mono text-gray-900" x-text="rec.period"></td>
                                    <td class="px-5 py-3 font-mono font-bold text-gray-800" x-text="formatMoney(rec.totalSales)"></td>
                                    <td class="px-5 py-3 font-mono font-bold text-[#C0422A]" x-text="formatMoney(rec.commissionAmount)"></td>
                                    <td class="px-5 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider"
                                              :class="rec.status === 'paid' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                              x-text="rec.status === 'paid' ? '✓ Paid' : 'Unpaid'"></span>
                                    </td>
                                    <td class="px-5 py-3 text-gray-400 font-mono text-[11px]" x-text="rec.dueDate || '7th of month'"></td>
                                    <td class="px-5 py-3">
                                        <template x-if="rec.referenceNumber">
                                            <div class="space-y-0.5">
                                                <span class="font-mono text-xs font-bold text-gray-800 block" x-text="rec.referenceNumber"></span>
                                                <template x-if="rec.paymentProof">
                                                    <button type="button"
                                                            @click="openProofModal(rec.paymentProof, (selectedSeller?.seller?.shopName || selectedSeller?.seller?.name) + ' (' + rec.period + ') · Proof Receipt', rec.referenceNumber)"
                                                            class="text-[10px] text-[#C0422A] hover:underline font-bold cursor-pointer inline-flex items-center gap-1">
                                                        🔍 Proof Receipt
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!rec.referenceNumber">
                                            <span class="text-gray-300 italic text-[10px]">None submitted</span>
                                        </template>
                                    </td>
                                    <td class="px-5 py-3 text-gray-400 font-mono text-[11px]" x-text="rec.paidAt || '—'"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer & Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-100 flex-wrap gap-3">
                <a :href="'/superadmin/sellers?search=' + encodeURIComponent(selectedSeller?.seller?.shopName || selectedSeller?.seller?.name || '')"
                   class="inline-flex items-center gap-1.5 text-xs font-bold text-gray-600 hover:text-[#C0422A] transition-colors">
                    <span>Manage Shop in Artisan Registry →</span>
                </a>

                <div class="flex items-center gap-2">
                    <template x-if="selectedSeller?.seller?.status === 'frozen'">
                        <button type="button"
                                @click="openUnfreezeModal(selectedSeller?.seller?.id, selectedSeller?.seller?.shopName || selectedSeller?.seller?.name, selectedSeller?.seller?.email, selectedSeller?.seller?.violationReason, selectedSeller?.paymentMethod, selectedSeller?.referenceNumber, selectedSeller?.paymentProof); showStatementModal = false;"
                                class="px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl uppercase tracking-wider transition-all cursor-pointer shadow-xs">
                            🔓 Unfreeze Account
                        </button>
                    </template>
                    <template x-if="selectedSeller?.seller?.status !== 'frozen' && selectedSeller?.totalOutstandingBalance > 0">
                        <button type="button"
                                @click="openFreezeModal(selectedSeller?.seller?.id, selectedSeller?.seller?.shopName || selectedSeller?.seller?.name, selectedSeller?.commissionAmount); showStatementModal = false;"
                                class="px-4 py-2.5 bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-600 hover:text-white text-xs font-bold rounded-xl uppercase tracking-wider transition-all cursor-pointer">
                            ❄️ Freeze Account
                        </button>
                    </template>

                    <button type="button" @click="showStatementModal = false" class="px-5 py-2.5 bg-gray-100 text-gray-700 hover:bg-gray-200 text-xs font-bold rounded-xl uppercase tracking-wider transition-all cursor-pointer">
                        Close Statement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Dedicated Proof of Payment Zoom Modal (Z-Index 60 to appear over any other modal) -->
    <div x-show="showProofModal"
         x-cloak
         style="display: none;"
         class="fixed inset-0 z-60 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         @click.self="showProofModal = false">
        
        <div class="relative bg-white rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-gray-100 overflow-hidden space-y-4 max-h-[92vh] flex flex-col"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95">
            
            <div class="flex items-center justify-between border-b border-gray-100 pb-3 shrink-0">
                <div>
                    <div class="text-[9px] font-black uppercase tracking-widest text-[#C0422A]">Remittance Verification</div>
                    <h3 class="font-serif text-lg font-bold text-gray-900" x-text="proofTitle || 'Proof of Payment Receipt'"></h3>
                    <template x-if="proofRef">
                        <div class="text-xs font-mono text-blue-700 font-bold mt-0.5" x-text="'Ref #: ' + proofRef"></div>
                    </template>
                </div>
                <button type="button" @click="showProofModal = false" class="text-gray-400 hover:text-gray-700 p-2 rounded-xl hover:bg-gray-100 transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-auto bg-[#F8F7F4] rounded-2xl border border-gray-200 p-2 flex items-center justify-center min-h-60 max-h-[65vh]">
                <img :src="proofImageUrl" class="max-h-[60vh] max-w-full object-contain rounded-xl shadow-xs" alt="Payment Proof">
            </div>

            <div class="flex items-center justify-between pt-2 shrink-0">
                <a :href="proofImageUrl" target="_blank" download class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-bold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Open in Full Tab / Download</span>
                </a>
                <button type="button" @click="showProofModal = false" class="px-5 py-2 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold uppercase tracking-wider rounded-xl transition-all cursor-pointer shadow-xs">
                    Close Preview
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function commissionsPage() {
        return {
            // Statement Modal State
            showStatementModal: false,
            selectedSeller: null,
            openStatementModal(sellerData) {
                this.selectedSeller = sellerData;
                this.showStatementModal = true;
            },
            formatMoney(val) {
                return '₱' + parseFloat(val || 0).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
            },

            // Proof Zoom Modal State
            showProofModal: false,
            proofImageUrl: '',
            proofTitle: '',
            proofRef: '',
            openProofModal(url, title = 'Proof of Payment Receipt', ref = '') {
                if (!url) return;
                let fullUrl = url;
                if (!url.startsWith('http') && !url.startsWith('/storage/') && !url.startsWith('/uploads/')) {
                    fullUrl = '/storage/' + url.replace(/^\//, '');
                }
                this.proofImageUrl = fullUrl;
                this.proofTitle = title;
                this.proofRef = ref;
                this.showProofModal = true;
            },

            // Freeze Modal State
            showModal: false,
            targetSellerId: '',
            targetShopName: '',
            targetAmount: '',
            freezeUrl: '',
            openFreezeModal(id, shopName, amount) {
                this.targetSellerId = id;
                this.targetShopName = shopName;
                this.targetAmount   = amount;
                this.freezeUrl      = '/superadmin/shops/' + id + '/freeze';
                this.showModal      = true;
            },

            // Mark Paid Modal State
            showMarkPaidModal: false,
            markPaidSellerId: '',
            markPaidShopName: '',
            markPaidEmail: '',
            markPaidAmount: '',
            markPaidMethod: '',
            markPaidRef: '',
            markPaidProof: '',
            markPaidUrl: '',
            openMarkPaidModal(id, shopName, email, amount, method, ref, proof) {
                this.markPaidSellerId = id;
                this.markPaidShopName = shopName;
                this.markPaidEmail    = email;
                this.markPaidAmount   = amount;
                this.markPaidMethod   = method;
                this.markPaidRef      = ref;
                this.markPaidProof    = proof;
                this.markPaidUrl      = '/superadmin/commissions/' + id + '/mark-paid';
                this.showMarkPaidModal = true;
            },

            // Unfreeze Modal State
            showUnfreezeModal: false,
            unfreezeSellerId: '',
            unfreezeShopName: '',
            unfreezeEmail: '',
            unfreezeReason: '',
            unfreezeMethod: '',
            unfreezeRef: '',
            unfreezeProof: '',
            unfreezeUrl: '',
            openUnfreezeModal(id, shopName, email, reason, method, ref, proof) {
                this.unfreezeSellerId = id;
                this.unfreezeShopName = shopName;
                this.unfreezeEmail    = email;
                this.unfreezeReason   = reason;
                this.unfreezeMethod   = method;
                this.unfreezeRef      = ref;
                this.unfreezeProof    = proof;
                this.unfreezeUrl      = '/superadmin/shops/' + id + '/unfreeze';
                this.showUnfreezeModal = true;
            }
        }
    }
</script>
@endpush
@endsection
