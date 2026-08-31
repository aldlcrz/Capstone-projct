@extends('layouts.app')

@section('content')
<div style="min-height:calc(100vh - 80px);background-color:#FAF8F5;padding:32px 16px;">
    <div style="max-width:750px;margin:0 auto;background-color:#FDFBF7;border:1px solid #EAE2D2;border-radius:28px;box-shadow:0 20px 50px rgba(0,0,0,0.06);padding:28px 24px;color:#1E1915;">

        {{-- Top Header --}}
        <div class="flex items-center justify-between gap-4 pb-4 border-b border-[#EAE1D0]">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-2xl flex items-center justify-center shrink-0 shadow-xs" style="background:#1E1915;color:#C49520;">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                </div>
                <div>
                    <span class="text-[9px] font-black uppercase tracking-widest text-[#C49520]">🛡️ Trust &amp; Safety</span>
                    <h1 class="font-serif text-xl sm:text-2xl font-bold tracking-tight text-[#1E1915]">My Submitted Reports</h1>
                </div>
            </div>

            <a href="{{ route('profile') }}" class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider text-[#766C60] bg-white border border-[#E8DECB] hover:bg-[#FAF5EA] transition-colors shadow-2xs">
                ← Profile
            </a>
        </div>

        {{-- Reports List --}}
        <div class="mt-6 space-y-4">
            @forelse($reports as $report)
                @php
                    $statusConfig = match($report->status) {
                        'Pending'      => ['bg' => '#FEF9EE', 'border' => '#F6DFA0', 'text' => '#A16D19', 'label' => '⏳ Pending Review'],
                        'Under Review' => ['bg' => '#EEF3FE', 'border' => '#B8CEFA', 'text' => '#2E5FCA', 'label' => '🔍 Under Investigation'],
                        'Resolved'     => ['bg' => '#F0F4EF', 'border' => '#C5D9B8', 'text' => '#4A6741', 'label' => '✓ Resolved'],
                        'Dismissed'    => ['bg' => '#F7F4EC', 'border' => '#E0D9CC', 'text' => '#766C60', 'label' => '— Dismissed'],
                        default        => ['bg' => '#FDF8EE', 'border' => '#E8DECB', 'text' => '#766C60', 'label' => $report->status],
                    };
                    $rType = $report->reportType ?? (!empty($report->productId) ? 'product' : 'account');
                @endphp

                <div class="p-4 sm:p-5 rounded-2xl bg-white border border-[#E8DECB] shadow-xs space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-xs font-black text-[#1E1915]">{{ $report->getReportCode() }}</span>
                            <span class="text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full {{ $rType === 'product' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ $rType === 'product' ? '📦 Product' : '👤 Account' }}
                            </span>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full"
                              style="background: {{ $statusConfig['bg'] }}; color: {{ $statusConfig['text'] }}; border: 1px solid {{ $statusConfig['border'] }};">
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>

                    <div>
                        <div class="text-sm font-bold text-[#1E1915]">{{ $report->reason }}</div>
                        <p class="text-xs text-[#766C60] mt-0.5 leading-relaxed">{{ $report->description }}</p>
                    </div>

                    {{-- Target Preview --}}
                    @if($report->product)
                        <div class="flex items-center gap-2 p-2 rounded-xl bg-[#FAF5EA] border border-[#E8DECB]">
                            <img src="{{ $report->product->primary_image ?? '/uploads/products/default.jpg' }}" class="w-8 h-8 rounded-lg object-cover">
                            <span class="text-xs font-bold text-[#1E1915] truncate">{{ $report->product->name }}</span>
                        </div>
                    @elseif($report->reported)
                        <div class="text-[11px] text-[#766C60]">
                            Reported Shop: <strong class="text-[#1E1915]">{{ $report->reported->shopName ?: $report->reported->name }}</strong>
                        </div>
                    @endif

                    {{-- Public Timeline Milestones --}}
                    @if($report->timelineEvents->isNotEmpty())
                        <div class="pt-2 border-t border-[#F0EAD8] space-y-1.5">
                            <div class="text-[9px] font-black uppercase tracking-widest text-[#A09585]">Status Updates</div>
                            @foreach($report->timelineEvents as $tEvent)
                                <div class="flex items-center justify-between text-[10px] text-[#766C60]">
                                    <span class="font-bold text-[#1E1915]">• {{ $tEvent->title }}</span>
                                    <span>{{ $tEvent->created_at->format('M d, Y') }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="text-[9px] text-[#A09585] pt-1">
                        Submitted on {{ $report->createdAt->format('F j, Y \a\t g:i A') }}
                    </div>
                </div>
            @empty
                <div class="py-16 text-center rounded-2xl border-2 border-dashed border-[#E8DECB] bg-white">
                    <div class="text-2xl mb-2">🛡️</div>
                    <h3 class="font-serif text-base font-bold text-[#1E1915]">No Reports Filed</h3>
                    <p class="text-xs text-[#766C60] mt-1 max-w-xs mx-auto">
                        You have not submitted any trust &amp; safety reports. When you report concerns regarding shops or products, they will appear here.
                    </p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($reports->hasPages())
            <div class="mt-6">
                {{ $reports->links() }}
            </div>
        @endif

    </div>
</div>
@endsection
