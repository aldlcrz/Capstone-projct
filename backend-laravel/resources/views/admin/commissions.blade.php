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
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-400 italic">No verified seller accounts found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

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
        showProofModal: false,
        proofUrl: '',
        proofTitle: '',

        openProofModal(url, title, ref) {
            this.proofUrl = url;
            this.proofTitle = title;
            this.showProofModal = true;
        }
    };
}
</script>
@endsection
