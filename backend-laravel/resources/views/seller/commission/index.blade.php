@extends('layouts.seller')

@section('content')
<div class="space-y-8 max-w-6xl">
    <div>
        <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Financial Settlement</div>
        <h1 class="font-serif text-3xl font-bold text-[#2A2A28]">Seller <span class="text-gray-400 font-light italic">Commission Payment</span></h1>
        <p class="text-xs text-gray-500 mt-1">Settle your monthly platform commission to maintain an active seller account.</p>
    </div>

    {{-- Monthly Summary Card --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-2">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Current Period</div>
            <div class="text-2xl font-black text-black">{{ \Carbon\Carbon::parse($period . '-01')->format('F Y') }}</div>
            <div class="text-xs text-gray-500">Commission due by 10th of next month</div>
        </div>

        <div class="bg-white rounded-3xl p-6 border border-gray-100 shadow-sm space-y-2">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Gross Sales ({{ $rate }}%)</div>
            <div class="text-2xl font-black text-[#C0422A]">₱{{ number_format($totalSales, 2) }}</div>
            <div class="text-xs text-gray-500">Total completed & active orders</div>
        </div>

        <div class="bg-[#2A2A28] text-white rounded-3xl p-6 shadow-xl space-y-2 relative overflow-hidden">
            <div class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Net Commission Due</div>
            <div class="text-3xl font-black text-amber-400">₱{{ number_format($commissionDue, 2) }}</div>
            <div class="text-xs text-gray-300">
                Status: 
                @if($currentRecord && $currentRecord->status === 'paid')
                    <span class="font-bold text-green-400 uppercase">✓ Paid</span>
                @elseif($currentRecord && $currentRecord->status === 'verification_pending')
                    <span class="font-bold text-amber-400 uppercase">⏳ Verification Pending</span>
                @else
                    <span class="font-bold text-red-400 uppercase">Payment Due</span>
                @endif
            </div>
        </div>
    </div>

    {{-- Super Admin Payment Accounts & Submit Form --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Payment Methods / QR Codes --}}
        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm space-y-6">
            <h3 class="text-sm font-black uppercase tracking-widest text-black flex items-center gap-2">
                <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                Super Admin Payment Accounts
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                {{-- GCash --}}
                <div class="p-4 bg-blue-50/50 border border-blue-100 rounded-2xl space-y-3 text-center">
                    <div class="text-xs font-bold text-blue-700 uppercase tracking-widest">GCash Account</div>
                    <div class="text-sm font-black text-black select-all">{{ $paymentSettings['gcash_number'] ?: 'Not provided' }}</div>
                    @if($paymentSettings['gcash_qr'])
                        <img src="{{ str_starts_with($paymentSettings['gcash_qr'], 'http') || str_starts_with($paymentSettings['gcash_qr'], '/') ? $paymentSettings['gcash_qr'] : asset('storage/' . $paymentSettings['gcash_qr']) }}" class="w-36 h-36 object-contain mx-auto rounded-xl border border-white shadow-sm">
                    @else
                        <div class="h-36 bg-white/60 rounded-xl flex items-center justify-center text-[10px] text-gray-400 italic">No QR uploaded</div>
                    @endif
                </div>

                {{-- Maya --}}
                <div class="p-4 bg-emerald-50/50 border border-emerald-100 rounded-2xl space-y-3 text-center">
                    <div class="text-xs font-bold text-emerald-700 uppercase tracking-widest">Maya Account</div>
                    <div class="text-sm font-black text-black select-all">{{ $paymentSettings['maya_number'] ?: 'Not provided' }}</div>
                    @if($paymentSettings['maya_qr'])
                        <img src="{{ str_starts_with($paymentSettings['maya_qr'], 'http') || str_starts_with($paymentSettings['maya_qr'], '/') ? $paymentSettings['maya_qr'] : asset('storage/' . $paymentSettings['maya_qr']) }}" class="w-36 h-36 object-contain mx-auto rounded-xl border border-white shadow-sm">
                    @else
                        <div class="h-36 bg-white/60 rounded-xl flex items-center justify-center text-[10px] text-gray-400 italic">No QR uploaded</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Submit Payment Proof Form --}}
        <div class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm space-y-6">
            <h3 class="text-sm font-black uppercase tracking-widest text-black flex items-center gap-2">
                <svg class="w-4 h-4 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Submit Commission Payment Proof
            </h3>

            @if($currentRecord && $currentRecord->status === 'paid')
                <div class="p-6 bg-green-50 border border-green-200 rounded-2xl text-center space-y-2">
                    <div class="w-12 h-12 rounded-full bg-green-500 text-white flex items-center justify-center text-xl font-bold mx-auto">✓</div>
                    <div class="text-sm font-bold text-green-900">Commission Settled for {{ $period }}</div>
                    <div class="text-xs text-green-700">Thank you! Your payment of ₱{{ number_format($currentRecord->commissionAmount, 2) }} was verified on {{ $currentRecord->paidAt ? $currentRecord->paidAt->format('M d, Y') : 'N/A' }}.</div>
                </div>
            @else
                <form action="{{ route('seller.commission.submit') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input type="hidden" name="period" value="{{ $period }}">

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1">Payment Method *</label>
                        <select name="paymentMethod" required class="w-full h-11 px-4 bg-gray-50 border border-gray-200 rounded-xl text-xs font-bold outline-none focus:border-[#C0422A]">
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1">Reference / Transaction Number *</label>
                        <input type="text" name="referenceNumber" required placeholder="e.g. 10029384812" class="w-full h-11 px-4 bg-gray-50 border border-gray-200 rounded-xl text-xs font-semibold outline-none focus:border-[#C0422A]">
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1">Proof of Payment Screenshot *</label>
                        <input type="file" name="paymentProof" required accept="image/*" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-gray-500 uppercase tracking-widest block mb-1">Notes (Optional)</label>
                        <textarea name="notes" rows="2" placeholder="Additional details..." class="w-full p-3 bg-gray-50 border border-gray-200 rounded-xl text-xs outline-none focus:border-[#C0422A]"></textarea>
                    </div>

                    <button type="submit" class="w-full h-12 bg-black text-white rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-[#C0422A] transition-all shadow-md">
                        Submit Payment Proof
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Payment History Table --}}
    <div class="bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden space-y-4">
        <div class="px-6 py-4 border-b border-gray-50 flex items-center justify-between">
            <h3 class="text-sm font-black uppercase tracking-widest text-black">Commission Settlement History</h3>
        </div>
        <table class="w-full text-left">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Period</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Sales</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Commission</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Method / Ref</th>
                    <th class="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($pastRecords as $rec)
                <tr class="hover:bg-gray-50/50 transition-all text-xs">
                    <td class="px-6 py-4 font-bold text-black">{{ \Carbon\Carbon::parse($rec->period . '-01')->format('M Y') }}</td>
                    <td class="px-6 py-4 font-semibold text-gray-700">₱{{ number_format($rec->totalSales, 2) }}</td>
                    <td class="px-6 py-4 font-bold text-[#C0422A]">₱{{ number_format($rec->commissionAmount, 2) }}</td>
                    <td class="px-6 py-4">
                        @if($rec->referenceNumber)
                            <div class="font-bold text-black">{{ $rec->paymentMethod }}</div>
                            <div class="text-[10px] text-gray-400">Ref: {{ $rec->referenceNumber }}</div>
                        @else
                            <span class="text-gray-400 italic">None</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($rec->status === 'paid')
                            <span class="px-3 py-1 bg-green-50 text-green-700 rounded-full text-[9px] font-black uppercase tracking-widest">Paid</span>
                        @elseif($rec->status === 'verification_pending')
                            <span class="px-3 py-1 bg-amber-50 text-amber-700 rounded-full text-[9px] font-black uppercase tracking-widest">Verification Pending</span>
                        @else
                            <span class="px-3 py-1 bg-red-50 text-red-600 rounded-full text-[9px] font-black uppercase tracking-widest">Unpaid</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-xs text-gray-400 italic">No past commission payment records.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
