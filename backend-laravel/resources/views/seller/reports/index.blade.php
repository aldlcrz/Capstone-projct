@extends('layouts.seller')

@section('content')
<div class="space-y-6 sm:space-y-8 pb-28 lg:pb-12" x-data="reportsConcerns()">

    {{-- ══ HEADER ══════════════════════════════════════════════════════════ --}}
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 pb-2 border-b" style="border-color: #E8DECB;">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-extrabold uppercase tracking-[0.25em]" style="color: #C49520;">⚠ Trust &amp; Safety</span>
                <span class="text-xs" style="color: #E8DECB;">•</span>
                <span class="text-[10px] font-semibold tracking-wider uppercase" style="color: #766C60;">Shop Reports</span>
            </div>
            <h1 class="font-serif text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight" style="color: #1E1915;">
                Reports &amp; <span class="italic font-normal" style="color: #766C60;">Concerns</span>
            </h1>
            <p class="text-xs sm:text-sm font-medium mt-1" style="color: #766C60;">
                Customer concerns filed against your shop. All reports are reviewed by our Trust &amp; Safety team.
            </p>
        </div>

        {{-- Summary badges --}}
        <div class="flex items-center gap-2 flex-wrap shrink-0">
            <div class="px-3.5 py-2 rounded-xl text-center shadow-xs" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                <div class="text-[8px] font-bold uppercase tracking-widest" style="color: #766C60;">Total</div>
                <div class="text-lg font-black font-sans" style="color: #1E1915;">{{ $counts['total'] }}</div>
            </div>
            <div class="px-3.5 py-2 rounded-xl text-center shadow-xs" style="background: #FEF9EE; border: 1px solid #F6DFA0;">
                <div class="text-[8px] font-bold uppercase tracking-widest" style="color: #A16D19;">Pending</div>
                <div class="text-lg font-black font-sans" style="color: #C49520;">{{ $counts['pending'] }}</div>
            </div>
            <div class="px-3.5 py-2 rounded-xl text-center shadow-xs" style="background: #F0F4EF; border: 1px solid #C5D9B8;">
                <div class="text-[8px] font-bold uppercase tracking-widest" style="color: #4A6741;">Resolved</div>
                <div class="text-lg font-black font-sans" style="color: #4A6741;">{{ $counts['resolved'] }}</div>
            </div>
        </div>
    </div>

    {{-- ══ NOTICE BANNER ══════════════════════════════════════════════════ --}}
    <div class="flex items-start gap-3 p-4 rounded-2xl" style="background: #FEF9EE; border: 1px solid #F6DFA0;">
        <div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" style="background: #FDF8EE; border: 1px solid #E8DECB; color: #C49520;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <div class="text-xs font-bold" style="color: #A16D19;">How reports work</div>
            <p class="text-[11px] mt-0.5 leading-relaxed" style="color: #766C60;">
                When a customer files a concern against your shop, our Trust &amp; Safety team reviews it. Reporter identities are kept confidential. Cooperate with any admin follow-up to resolve concerns quickly. Unresolved reports may affect your shop standing.
            </p>
        </div>
    </div>

    {{-- ══ FILTER PILLS ════════════════════════════════════════════════════ --}}
    <div class="p-3 sm:p-4 rounded-2xl flex items-center gap-2 overflow-x-auto no-scrollbar shadow-xs" style="background: #FFFCF7; border: 1px solid #E8DECB;">
        @php
            $filterOptions = [
                'all'          => 'All Reports (' . $counts['total'] . ')',
                'Pending'      => 'Pending (' . $counts['pending'] . ')',
                'Under Review' => 'Under Review (' . $counts['under_review'] . ')',
                'Resolved'     => 'Resolved (' . $counts['resolved'] . ')',
                'Dismissed'    => 'Dismissed (' . $counts['dismissed'] . ')',
            ];
        @endphp
        @foreach($filterOptions as $val => $label)
            <button @click="activeFilter = '{{ $val }}'"
                :style="activeFilter === '{{ $val }}'
                    ? 'background:#1E1915; color:#FFFCF7; border:1px solid #C49520;'
                    : 'background:#FDF8EE; color:#1E1915; border:1px solid #E8DECB;'"
                class="px-4 py-2 rounded-xl text-[10px] font-extrabold uppercase tracking-widest transition-all shrink-0 cursor-pointer shadow-2xs flex items-center gap-1.5">
                <span x-show="activeFilter === '{{ $val }}'" style="color:#C49520;">✓</span>
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- ══ REPORTS LIST ═════════════════════════════════════════════════════ --}}
    @if($reports->isEmpty())
        <div class="py-20 text-center rounded-3xl" style="background: #FFFCF7; border: 2px dashed #E8DECB;">
            <div class="w-14 h-14 rounded-2xl mx-auto mb-4 flex items-center justify-center text-2xl" style="background: #FDF8EE; border: 1px solid #E8DECB;">
                🛡️
            </div>
            <h3 class="font-serif text-lg font-bold mb-1" style="color: #1E1915;">No reports filed against your shop</h3>
            <p class="text-xs max-w-xs mx-auto" style="color: #766C60;">
                Your shop has a clean record. Keep delivering authentic, high-quality artisan creations to maintain it.
            </p>
        </div>
    @else
        <div class="space-y-3 sm:space-y-4">
            @foreach($reports as $report)
                @php
                    $statusConfig = match($report->status) {
                        'Pending'      => ['bg' => '#FEF9EE', 'border' => '#F6DFA0', 'text' => '#A16D19', 'dot' => '#C49520', 'pulse' => true,  'label' => '⏳ Pending'],
                        'Under Review' => ['bg' => '#EEF3FE', 'border' => '#B8CEFA', 'text' => '#2E5FCA', 'dot' => '#4A7BFF', 'pulse' => true,  'label' => '🔍 Under Review'],
                        'Resolved'     => ['bg' => '#F0F4EF', 'border' => '#C5D9B8', 'text' => '#4A6741', 'dot' => '#4A6741', 'pulse' => false, 'label' => '✓ Resolved'],
                        'Dismissed'    => ['bg' => '#F7F4EC', 'border' => '#E0D9CC', 'text' => '#766C60', 'dot' => '#A09585', 'pulse' => false, 'label' => '— Dismissed'],
                        default        => ['bg' => '#FDF8EE', 'border' => '#E8DECB', 'text' => '#766C60', 'dot' => '#A09585', 'pulse' => false, 'label' => $report->status],
                    };
                    $reportId = strtoupper(substr($report->id, -8));
                @endphp

                <div
                    x-show="activeFilter === 'all' || activeFilter === '{{ addslashes($report->status) }}'"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-data="{ expanded: false }"
                    class="rounded-2xl sm:rounded-3xl shadow-xs overflow-hidden"
                    style="background: #FFFCF7; border: 1px solid #E8DECB;">

                    {{-- Card top --}}
                    <div class="p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-3 min-w-0 flex-1">
                                <div class="w-2.5 h-2.5 rounded-full mt-1.5 shrink-0 {{ $statusConfig['pulse'] ? 'animate-pulse' : '' }}"
                                     style="background: {{ $statusConfig['dot'] }};"></div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        <span class="text-[9px] font-black uppercase tracking-widest font-sans" style="color: #A09585;">
                                            RPT-{{ $reportId }}
                                        </span>
                                        <span class="text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                                              style="background: {{ $statusConfig['bg'] }}; color: {{ $statusConfig['text'] }}; border: 1px solid {{ $statusConfig['border'] }};">
                                            {{ $statusConfig['label'] }}
                                        </span>
                                    </div>
                                    <h3 class="text-sm font-bold tracking-tight" style="color: #1E1915;">
                                        {{ $report->reason }}
                                    </h3>
                                    <p class="text-xs mt-0.5 line-clamp-2 leading-relaxed" style="color: #766C60;">
                                        {{ $report->description }}
                                    </p>
                                </div>
                            </div>

                            <div class="text-right shrink-0 space-y-1.5">
                                <div class="text-[10px] font-semibold font-sans" style="color: #A09585;">
                                    {{ $report->createdAt ? $report->createdAt->format('M d, Y') : '—' }}
                                </div>
                                <div class="text-[9px]" style="color: #C0B090;">
                                    {{ $report->createdAt ? $report->createdAt->diffForHumans() : '' }}
                                </div>
                                <button @click="expanded = !expanded"
                                        class="text-[9px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg transition-all cursor-pointer block"
                                        style="background: #FDF8EE; border: 1px solid #E8DECB; color: #766C60;"
                                        onmouseover="this.style.borderColor='#C49520'; this.style.color='#C49520';"
                                        onmouseout="this.style.borderColor='#E8DECB'; this.style.color='#766C60';">
                                    <span x-text="expanded ? '▲ Hide' : '▼ Details'"></span>
                                </button>
                            </div>
                        </div>

                        {{-- Meta row --}}
                        <div class="flex items-center gap-3 mt-3 pt-3 border-t flex-wrap" style="border-color: #F0EAD8;">
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" style="color: #A09585;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                <span class="text-[10px] font-semibold" style="color: #766C60;">Anonymous Customer</span>
                            </div>
                            <span style="color: #E8DECB;">•</span>
                            <div class="flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" style="color: #A09585;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                <span class="text-[10px] font-semibold" style="color: #766C60;">
                                    {{ $report->createdAt ? $report->createdAt->format('F j, Y \a\t g:i A') : '—' }}
                                </span>
                            </div>
                            @if($report->evidence)
                                <span style="color: #E8DECB;">•</span>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span class="text-[10px] font-semibold" style="color: #C49520;">Evidence attached</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Expandable detail panel --}}
                    <div x-show="expanded"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="border-t px-4 sm:px-5 py-4 space-y-4"
                         style="border-color: #E8DECB; background: #FDF8EE;">

                        {{-- Full description --}}
                        <div>
                            <div class="text-[9px] font-extrabold uppercase tracking-widest mb-1.5" style="color: #A09585;">Customer's Concern</div>
                            <p class="text-xs leading-relaxed p-3 rounded-xl" style="background: #FFFCF7; border: 1px solid #E8DECB; color: #1E1915;">
                                {{ $report->description }}
                            </p>
                        </div>

                        {{-- Evidence --}}
                        @if($report->evidence)
                        <div>
                            <div class="text-[9px] font-extrabold uppercase tracking-widest mb-1.5" style="color: #A09585;">Submitted Evidence</div>
                            @php
                                $isImg = preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $report->evidence) || str_starts_with($report->evidence, 'data:image') || str_contains($report->evidence, '/storage/') || str_contains($report->evidence, '/uploads/');
                                $evidenceUrl = str_starts_with($report->evidence, 'http') || str_starts_with($report->evidence, '/') 
                                    ? $report->evidence 
                                    : (str_starts_with($report->evidence, 'uploads/') || str_starts_with($report->evidence, 'storage/') ? '/' . $report->evidence : null);
                            @endphp
                            @if($isImg && $evidenceUrl)
                                <div class="relative group rounded-xl overflow-hidden border max-w-xs bg-white shadow-xs" style="border-color: #E8DECB;">
                                    <img src="{{ $evidenceUrl }}" alt="Report Evidence" class="w-full h-44 object-cover group-hover:scale-105 transition-transform duration-300 cursor-pointer" onclick="window.open('{{ $evidenceUrl }}', '_blank')">
                                    <a href="{{ $evidenceUrl }}" target="_blank" class="absolute bottom-2 right-2 px-2.5 py-1 text-white text-[9px] font-bold uppercase tracking-wider rounded-lg backdrop-blur-xs flex items-center gap-1 transition-all" style="background: rgba(30, 25, 21, 0.85);">
                                        <span>View Full</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            @else
                                <p class="text-xs leading-relaxed p-3 rounded-xl break-words" style="background: #FFFCF7; border: 1px solid #E8DECB; color: #766C60;">
                                    {{ $report->evidence }}
                                </p>
                            @endif
                        </div>
                        @endif

                        {{-- Admin Notes --}}
                        @if($report->adminNotes)
                        <div class="p-3.5 rounded-xl" style="background: #FFFCF7; border: 1px solid #C49520;">
                            <div class="text-[9px] font-extrabold uppercase tracking-widest mb-1.5 flex items-center gap-1.5" style="color: #C49520;">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                Admin Notes
                            </div>
                            <p class="text-xs leading-relaxed" style="color: #1E1915;">{{ $report->adminNotes }}</p>
                        </div>
                        @endif

                        {{-- Action taken --}}
                        @if($report->actionTaken)
                        <div class="p-3.5 rounded-xl" style="background: #F0F4EF; border: 1px solid #C5D9B8;">
                            <div class="text-[9px] font-extrabold uppercase tracking-widest mb-1.5" style="color: #4A6741;">Action Taken by Admin</div>
                            <p class="text-xs leading-relaxed" style="color: #1E1915;">{{ $report->actionTaken }}</p>
                        </div>
                        @endif

                        {{-- Guidance for open reports --}}
                        @if(in_array($report->status, ['Pending', 'Under Review']))
                        <div class="flex items-start gap-2.5 p-3 rounded-xl" style="background: #FFFCF7; border: 1px solid #E8DECB;">
                            <svg class="w-4 h-4 mt-0.5 shrink-0" style="color: #C49520;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-[11px] leading-relaxed" style="color: #766C60;">
                                Our Trust &amp; Safety team is reviewing this concern. No action is required from you at this time. If additional information is needed, an admin will contact you directly through your registered email.
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

<script>
function reportsConcerns() {
    return {
        activeFilter: 'all',
        targetReportId: '',
        init() {
            const urlParams = new URLSearchParams(window.location.search);
            const viewId = urlParams.get('view_report');
            if (viewId) {
                this.targetReportId = viewId;
            }
            const filterParam = urlParams.get('filter');
            if (filterParam) {
                this.activeFilter = filterParam;
            }
        }
    };
}
document.addEventListener('alpine:init', () => {
    Alpine.data('reportsConcerns', reportsConcerns);
});
</script>
@endsection
