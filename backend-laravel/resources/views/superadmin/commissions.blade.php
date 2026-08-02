@extends('layouts.superadmin')

@section('content')
<div x-data="commissionsPage()" class="space-y-8">
    <!-- Header & Period Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-xs font-bold text-[#C0422A] uppercase tracking-widest mb-1">Super Admin Governance</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">Commission &amp; <span class="text-[#C0422A] italic">Shop Sales</span></h1>
        </div>

        <form method="GET" action="{{ route('superadmin.commissions') }}" class="flex items-center gap-2">
            <label class="text-xs text-gray-400 font-bold uppercase tracking-wider">Select Period:</label>
            <select name="period" onchange="this.form.submit()"
                    class="px-4 py-2.5 bg-white border border-[#E5DDD5] rounded-xl text-[#3D2B1F] text-xs font-bold focus:outline-none focus:border-[#C0422A] transition-all">
                @foreach($periods as $p)
                    <option value="{{ $p }}" {{ $period === $p ? 'selected' : '' }}>{{ $p }}</option>
                @endforeach
                @if(!$periods->contains($period))
                    <option value="{{ $period }}" selected>{{ $period }} (Current)</option>
                @endif
            </select>
        </form>
    </div>

    <!-- Summary Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white border border-[#E5DDD5] rounded-2xl p-5 shadow-sm">
            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Period Total Sales</div>
            <div class="text-xl font-black text-[#3D2B1F]">₱{{ number_format($periodTotalSales, 2) }}</div>
        </div>
        <div class="bg-white border border-[#E5DDD5] rounded-2xl p-5 shadow-sm">
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-1">Total Commission Due ({{ $rate }}%)</div>
            <div class="text-xl font-black text-[#C0422A]">₱{{ number_format($periodTotalDue, 2) }}</div>
        </div>
        <div class="bg-white border border-[#E5DDD5] rounded-2xl p-5 shadow-sm">
            <div class="text-[10px] font-bold text-green-600 uppercase tracking-widest mb-1">Total Collected</div>
            <div class="text-xl font-black text-green-600">₱{{ number_format($periodTotalPaid, 2) }}</div>
        </div>
        <div class="bg-white border border-[#E5DDD5] rounded-2xl p-5 shadow-sm">
            <div class="text-[10px] font-bold text-red-500 uppercase tracking-widest mb-1">Unpaid Sellers</div>
            <div class="text-xl font-black text-red-500">{{ $periodUnpaid }} <span class="text-xs text-gray-400 font-normal">Sellers</span></div>
        </div>
    </div>

    <!-- Global Rate Config -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-sm">
        <div class="space-y-1">
            <h3 class="text-sm font-bold text-[#3D2B1F] uppercase tracking-wider flex items-center gap-2">
                <span>⚙️ Global Commission Rate Configuration</span>
            </h3>
            <p class="text-xs text-gray-500">Default rate is <strong class="text-gray-700">5%</strong> of product price per order across all registered shops.</p>
        </div>

        <form action="{{ route('superadmin.commission-rate') }}" method="POST" class="flex items-center gap-2">
            @csrf
            <div class="relative">
                <input type="number" step="0.1" min="0" max="100" name="rate" value="{{ $rate }}" required
                       class="w-24 px-3 py-2 bg-[#F9F6F2] border border-[#E5DDD5] rounded-xl text-[#3D2B1F] text-xs font-bold text-right pr-6 focus:outline-none focus:border-[#C0422A] transition-all">
                <span class="absolute right-2.5 top-2 text-xs text-gray-400 font-bold">%</span>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#3D2B1F] hover:bg-[#C0422A] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                Save Rate
            </button>
        </form>
    </div>

    <!-- Currently Frozen Shops Section (if any) -->
    @if($frozenSellers->count() > 0)
    <div class="bg-blue-50 border border-blue-200 rounded-3xl p-6 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-bold text-blue-700 uppercase tracking-wider flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                ❄️ Currently Frozen Shops ({{ $frozenSellers->count() }})
            </h3>
            <span class="text-xs text-blue-500">Products remain visible to buyers, but purchases &amp; seller login are disabled.</span>
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
            <div class="bg-white border border-blue-200 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                <div>
                    <div class="text-sm font-bold text-[#3D2B1F] flex items-center gap-2">
                        {{ $sellerObj->shopName ?: $sellerObj->name }}
                        <span class="px-2 py-0.5 bg-blue-100 text-blue-600 border border-blue-200 text-[9px] font-bold rounded-full uppercase">Frozen</span>
                    </div>
                    <div class="text-[11px] text-gray-400 mt-0.5">{{ $sellerObj->email }}</div>
                    @if($fs['reason'])
                        <div class="text-[10px] text-gray-500 mt-1 italic">Reason: {{ $fs['reason'] }}</div>
                    @endif
                </div>

                <button type="button"
                        @click="openUnfreezeModal('{{ $sellerObj->id }}', '{{ $shopName }}', '{{ $email }}', '{{ $reason }}', '{{ $pm }}', '{{ $ref }}', '{{ $proof }}')"
                        class="px-4 py-2 bg-[#F7F3EE] text-[#3D2B1F] border border-[#E5DDD5] hover:bg-[#C0422A] hover:text-white hover:border-[#C0422A] rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                    🔓 Unfreeze
                </button>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- All Shops Commission Table -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl overflow-hidden shadow-sm">
        <div class="px-6 py-5 border-b border-[#E5DDD5] flex items-center justify-between">
            <h3 class="text-sm font-bold text-[#3D2B1F] uppercase tracking-wider">Per-Shop Sales &amp; Commission breakdown ({{ $period }})</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F7F3EE] border-b border-[#E5DDD5] text-gray-400 uppercase tracking-widest font-bold text-[10px]">
                        <th class="px-6 py-4">Shop &amp; Seller Details</th>
                        <th class="px-6 py-4">Period Sales</th>
                        <th class="px-6 py-4">Commission ({{ $rate }}%)</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Payment Info</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5DDD5]">
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
                    <tr class="hover:bg-[#F7F3EE] transition-all {{ $isFrozen ? 'bg-blue-50/50' : '' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl {{ $isFrozen ? 'bg-blue-100 text-blue-600 border border-blue-200' : 'bg-[#C0422A]/10 text-[#C0422A] border border-[#C0422A]/20' }} flex items-center justify-center font-bold text-sm">
                                    {{ strtoupper(substr($s['seller']->shopName ?: $s['seller']->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="text-sm font-bold text-[#3D2B1F] flex items-center gap-2">
                                        {{ $s['seller']->shopName ?: $s['seller']->name }}
                                        @if($isFrozen)
                                            <span class="px-2 py-0.5 bg-blue-100 text-blue-600 border border-blue-200 text-[9px] font-bold rounded-full uppercase">Frozen</span>
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-gray-400">{{ $s['seller']->name }} • {{ $s['seller']->email }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 font-bold text-[#3D2B1F] text-sm">
                            ₱{{ number_format($s['totalSales'], 2) }}
                        </td>

                        <td class="px-6 py-4 font-bold text-[#C0422A] text-sm">
                            ₱{{ number_format($s['commissionAmount'], 2) }}
                        </td>

                        <td class="px-6 py-4">
                            @if($s['status'] === 'paid')
                                <span class="px-3 py-1 bg-green-50 text-green-600 border border-green-200 rounded-full font-bold uppercase text-[9px]">Paid</span>
                            @else
                                <span class="px-3 py-1 bg-red-50 text-red-500 border border-red-200 rounded-full font-bold uppercase text-[9px]">Unpaid</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-gray-400">
                            @if($s['status'] === 'paid')
                                <div class="text-[10px]">
                                    <span class="text-green-600 font-bold">Settled</span>
                                    @if($s['paidAt'])
                                        <div class="text-gray-400">{{ \Carbon\Carbon::parse($s['paidAt'])->format('M d, Y') }}</div>
                                    @endif
                                </div>
                            @else
                                <div class="text-[10px] space-y-1">
                                    @if($s['referenceNumber'] || $s['paymentProof'])
                                        <div class="text-blue-600 font-bold flex items-center gap-1">
                                            <span>💳 {{ $s['paymentMethod'] ?? 'Payment' }}</span>
                                        </div>
                                        @if($s['referenceNumber'])
                                            <div class="font-mono text-gray-800 font-bold">Ref: {{ $s['referenceNumber'] }}</div>
                                        @endif
                                        @if($s['paymentProof'])
                                            <a href="{{ asset('storage/' . $s['paymentProof']) }}" target="_blank" 
                                               class="inline-flex items-center gap-1 text-[10px] text-[#C0422A] hover:underline font-bold">
                                                🔍 View Screenshot Proof
                                            </a>
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
                                            class="px-3 py-1.5 bg-green-50 text-green-600 border border-green-200 hover:bg-green-600 hover:text-white rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all">
                                        ✓ Mark Paid
                                    </button>
                                @endif

                                <!-- Freeze / Unfreeze Buttons -->
                                @if($isFrozen)
                                    <button type="button"
                                            @click="openUnfreezeModal('{{ $s['seller']->id }}', '{{ $shopName }}', '{{ $email }}', '{{ addslashes($s['seller']->violationReason ?? '') }}', '{{ $pm }}', '{{ $ref }}', '{{ $proof }}')"
                                            class="px-3 py-1.5 bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-600 hover:text-white rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all">
                                        🔓 Unfreeze
                                    </button>
                                @else
                                    <button type="button"
                                            @click="openFreezeModal('{{ $s['seller']->id }}', '{{ $shopName }}', '{{ $amountStr }}')"
                                            class="px-3 py-1.5 bg-red-50 text-red-500 border border-red-200 hover:bg-red-500 hover:text-white rounded-lg text-[10px] font-bold uppercase tracking-wider transition-all">
                                        ❄️ Freeze Shop
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
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
         style="display: none;"
         x-cloak>

        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-5">
            <div class="flex items-center gap-3 text-red-500">
                <div class="w-10 h-10 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-center font-bold text-lg">
                    ⚠️
                </div>
                <div>
                    <h3 class="text-base font-bold text-[#3D2B1F]">Confirm Account Freeze</h3>
                    <p class="text-xs text-gray-400">Action requires Super Admin confirmation</p>
                </div>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed">
                Are you sure you want to freeze shop <strong class="text-[#3D2B1F]" x-text="targetShopName"></strong>?
                <br><br>
                <span class="text-gray-500">• Current Unpaid Commission: <strong class="text-[#C0422A]">₱<span x-text="targetAmount"></span></strong></span><br>
                <span class="text-gray-500">• The seller will be notified immediately via notification.</span><br>
                <span class="text-gray-500">• Products will remain visible on the marketplace, but buyers will be unable to add them to cart or checkout.</span>
            </p>

            <form x-bind:action="freezeUrl" method="POST" class="space-y-4">
                @csrf @method('PATCH')
                <input type="hidden" name="period" value="{{ $period }}">

                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">Reason for Freeze</label>
                    <input type="text" name="reason" x-bind:value="'Unpaid commission for period {{ $period }} (₱' + targetAmount + ')'" required
                           class="w-full px-3 py-2 bg-[#F9F6F2] border border-[#E5DDD5] rounded-xl text-[#3D2B1F] text-xs focus:outline-none focus:border-[#C0422A] transition-all">
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" @click="showModal = false" class="px-4 py-2.5 bg-[#F7F3EE] text-gray-600 hover:text-[#3D2B1F] border border-[#E5DDD5] rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all">
                        Confirm Freeze
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Mark Paid Proof Confirmation Modal -->
    <div x-show="showMarkPaidModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
         style="display: none;"
         x-cloak>

        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center gap-3 text-green-600">
                <div class="w-10 h-10 rounded-2xl bg-green-50 border border-green-200 flex items-center justify-center font-bold text-lg">
                    💳
                </div>
                <div>
                    <h3 class="text-base font-bold text-[#3D2B1F]">Confirm Mark Commission as Paid</h3>
                    <p class="text-xs text-gray-400">Review seller payment verification proof before proceeding</p>
                </div>
            </div>

            <!-- Seller & Amount Summary -->
            <div class="bg-[#F9F6F2] border border-[#E5DDD5] rounded-2xl p-4 space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 font-bold uppercase tracking-wider text-[10px]">Shop / Seller:</span>
                    <span class="font-bold text-[#3D2B1F]" x-text="markPaidShopName"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 font-bold uppercase tracking-wider text-[10px]">Seller Email:</span>
                    <span class="text-gray-600" x-text="markPaidEmail"></span>
                </div>
                <div class="flex justify-between items-center border-t border-[#E5DDD5] pt-2">
                    <span class="text-gray-500 font-bold uppercase tracking-wider text-[10px]">Period &amp; Amount Due:</span>
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
                                <a x-bind:href="'/storage/' + markPaidProof" target="_blank" class="block group relative overflow-hidden rounded-xl border border-blue-200 bg-white">
                                    <img x-bind:src="'/storage/' + markPaidProof" class="w-full max-h-48 object-contain p-2 group-hover:scale-105 transition-all">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold transition-all">
                                        🔍 Click to Expand Proof
                                    </div>
                                </a>
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
            <form x-bind:action="markPaidUrl" method="POST" class="space-y-4 pt-2 border-t border-[#E5DDD5]">
                @csrf @method('PATCH')
                <input type="hidden" name="period" value="{{ $period }}">

                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showMarkPaidModal = false" class="px-4 py-2.5 bg-[#F7F3EE] text-gray-600 hover:text-[#3D2B1F] border border-[#E5DDD5] rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md">
                        ✓ Confirm &amp; Mark Paid
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Unfreeze Proof Confirmation Modal -->
    <div x-show="showUnfreezeModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
         style="display: none;"
         x-cloak>

        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-5 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center gap-3 text-blue-600">
                <div class="w-10 h-10 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center font-bold text-lg">
                    🔓
                </div>
                <div>
                    <h3 class="text-base font-bold text-[#3D2B1F]">Confirm Account Unfreeze</h3>
                    <p class="text-xs text-gray-400">Verify seller payment proof to restore shop login &amp; privileges</p>
                </div>
            </div>

            <!-- Seller Summary -->
            <div class="bg-[#F9F6F2] border border-[#E5DDD5] rounded-2xl p-4 space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 font-bold uppercase tracking-wider text-[10px]">Shop / Seller:</span>
                    <span class="font-bold text-[#3D2B1F]" x-text="unfreezeShopName"></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-500 font-bold uppercase tracking-wider text-[10px]">Seller Email:</span>
                    <span class="text-gray-600" x-text="unfreezeEmail"></span>
                </div>
                <template x-if="unfreezeReason">
                    <div class="flex justify-between items-center border-t border-[#E5DDD5] pt-2">
                        <span class="text-gray-500 font-bold uppercase tracking-wider text-[10px]">Freeze Reason:</span>
                        <span class="text-blue-600 font-semibold italic text-[11px]" x-text="unfreezeReason"></span>
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
                                <a x-bind:href="'/storage/' + unfreezeProof" target="_blank" class="block group relative overflow-hidden rounded-xl border border-blue-200 bg-white">
                                    <img x-bind:src="'/storage/' + unfreezeProof" class="w-full max-h-48 object-contain p-2 group-hover:scale-105 transition-all">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 flex items-center justify-center text-white text-xs font-bold transition-all">
                                        🔍 Click to Expand Proof
                                    </div>
                                </a>
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
            <form x-bind:action="unfreezeUrl" method="POST" class="space-y-4 pt-2 border-t border-[#E5DDD5]">
                @csrf @method('PATCH')

                <div class="flex items-center justify-end gap-3">
                    <button type="button" @click="showUnfreezeModal = false" class="px-4 py-2.5 bg-[#F7F3EE] text-gray-600 hover:text-[#3D2B1F] border border-[#E5DDD5] rounded-xl text-xs font-bold uppercase tracking-wider transition-all">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-md">
                        🔓 Confirm &amp; Unfreeze Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function commissionsPage() {
        return {
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
