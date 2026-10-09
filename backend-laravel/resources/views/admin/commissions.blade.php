@extends('layouts.admin')

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
                Commission &amp; <span class="text-[#C0422A] font-light italic">Shop Sales</span>
            </h1>
            <p class="text-[11px] text-gray-400 font-medium">Track platform gross merchandise value, cash commission balances, and artisan settlements</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.payouts') }}" class="px-4 py-2 bg-[#3D2B1F] hover:bg-[#C0422A] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all shadow-xs flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                <span>Seller Payouts</span>
            </a>

            <form method="GET" action="{{ route('admin.commissions') }}" class="flex items-center gap-2 bg-white p-2 rounded-2xl border border-gray-100 shadow-xs">
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
    </div>

    <!-- Summary KPI Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-all">
            <div class="flex items-center justify-between">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-wider">Gross Cash Sales</span>
            </div>
            <div>
                <div class="text-xs font-bold text-gray-400 uppercase tracking-wider">Commissionable Base</div>
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

    <!-- Seller Commission Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">
                Artisan Shops Commission Status ({{ $period }})
            </h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-160">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-[10px] font-bold text-gray-400 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Shop &amp; Seller</th>
                        <th class="px-6 py-3.5">Cash Product Sales</th>
                        <th class="px-6 py-3.5">Commission Due ({{ $rate }}%)</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5">Remittance Info</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($sellers as $s)
                    @php
                        $shopName = $s['seller']->shopName ?: $s['seller']->name;
                        $email = $s['seller']->email;
                        $amountStr = number_format($s['commissionAmount'], 2, '.', '');
                        $pm = addslashes($s['paymentMethod'] ?? '');
                        $ref = addslashes($s['referenceNumber'] ?? '');
                        $proof = addslashes($s['paymentProof'] ?? '');
                    @endphp
                    <tr class="hover:bg-amber-50/20 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900">{{ $shopName }}</div>
                            <div class="text-[10px] text-gray-400">{{ $s['seller']->name }} • {{ $s['seller']->email }}</div>
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
                            @elseif($s['status'] === 'verification_pending')
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full font-bold uppercase text-[9px]">⏳ Proof Submitted</span>
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
                                @if($s['status'] !== 'paid')
                                    <button type="button"
                                            @click="openMarkPaidModal('{{ $s['seller']->id }}', '{{ $shopName }}', '{{ $email }}', '{{ $amountStr }}', '{{ $pm }}', '{{ $ref }}', '{{ $proof }}')"
                                            class="px-3 py-1.5 bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-600 hover:text-white rounded-xl text-[10px] font-bold uppercase tracking-wider transition-all cursor-pointer">
                                        ✓ Mark Paid
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

    <!-- Mark Paid Modal -->
    <template x-teleport="body">
        <div x-show="showMarkPaidModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             @keydown.escape.window="showMarkPaidModal = false"
             @click.self="showMarkPaidModal = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
             style="display: none;"
             x-cloak>

            <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl border border-gray-100 space-y-5 max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="flex items-center gap-3 text-emerald-600">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center font-bold text-lg shrink-0">
                        💳
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">Confirm Mark Commission as Paid</h3>
                        <p class="text-xs text-gray-400">Review seller payment verification proof before proceeding</p>
                    </div>
                </div>

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

                <form x-bind:action="markPaidUrl" method="POST" class="space-y-4 pt-2 border-t border-gray-100">
                    @csrf @method('PATCH')
                    <input type="hidden" name="period" value="{{ $period }}">

                    <div>
                        <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Administrative Notes (Optional)</label>
                        <input type="text" name="notes" placeholder="e.g. Verified via GCash reference..."
                               class="w-full px-3.5 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 text-xs focus:outline-none focus:border-emerald-600 transition-all">
                    </div>

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
    </template>

    <!-- Proof Modal -->
    <template x-teleport="body">
        <div x-show="showProofModal"
             x-cloak
             style="display: none;"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
             @keydown.escape.window="showProofModal = false"
             @click.self="showProofModal = false">
            <div class="bg-white rounded-3xl p-6 max-w-lg w-full shadow-2xl space-y-4" @click.stop>
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-sm font-bold text-gray-900" x-text="proofTitle"></h3>
                    <button type="button" @click="showProofModal = false" class="text-gray-400 hover:text-gray-700">✕</button>
                </div>
                <div class="rounded-xl overflow-hidden border bg-gray-50">
                    <img x-bind:src="proofUrl" class="max-h-80 w-full object-contain mx-auto p-2">
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function commissionsPage() {
    return {
        showMarkPaidModal: false,
        showProofModal: false,
        markPaidShopName: '',
        markPaidEmail: '',
        markPaidAmount: '0.00',
        markPaidMethod: '',
        markPaidRef: '',
        markPaidProof: '',
        markPaidUrl: '',
        proofUrl: '',
        proofTitle: '',

        openMarkPaidModal(sellerId, shopName, email, amount, method, ref, proof) {
            this.markPaidShopName = shopName;
            this.markPaidEmail = email;
            this.markPaidAmount = amount;
            this.markPaidMethod = method;
            this.markPaidRef = ref;
            this.markPaidProof = proof;
            this.markPaidUrl = `/admin/commissions/${sellerId}/mark-paid`;
            this.showMarkPaidModal = true;
        },

        openProofModal(url, title, ref) {
            this.proofUrl = url;
            this.proofTitle = title;
            this.showProofModal = true;
        }
    };
}
</script>
@endsection
