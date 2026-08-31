@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="adminReportsManagement()">
    
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-black uppercase tracking-[0.2em] text-[#C49520]">🛡️ Trust &amp; Safety</span>
                <span class="text-xs text-gray-300">•</span>
                <span class="text-[10px] font-bold text-gray-500 uppercase tracking-wider">Moderation Console</span>
            </div>
            <h2 class="text-xl sm:text-2xl font-black text-[#3D2B1F] tracking-tight">Account &amp; Product Reports</h2>
            <p class="text-xs text-gray-500 mt-0.5">Review, investigate, and enforce platform trust &amp; safety policies</p>
        </div>

        {{-- Summary Stat Badges --}}
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.reports', ['status' => 'Pending']) }}" class="px-3 py-1.5 {{ $status == 'Pending' ? 'bg-[#3D2B1F] text-white' : 'bg-white text-gray-700' }} rounded-xl text-[10px] font-bold uppercase tracking-wider border border-gray-100 shadow-2xs">
                Pending ({{ $counts['pending'] }})
            </a>
            <a href="{{ route('admin.reports', ['status' => 'Under Review']) }}" class="px-3 py-1.5 {{ $status == 'Under Review' ? 'bg-[#3D2B1F] text-white' : 'bg-white text-gray-700' }} rounded-xl text-[10px] font-bold uppercase tracking-wider border border-gray-100 shadow-2xs">
                Under Review ({{ $counts['under_review'] }})
            </a>
            <a href="{{ route('admin.reports', ['status' => 'Resolved']) }}" class="px-3 py-1.5 {{ $status == 'Resolved' ? 'bg-[#3D2B1F] text-white' : 'bg-white text-gray-700' }} rounded-xl text-[10px] font-bold uppercase tracking-wider border border-gray-100 shadow-2xs">
                Resolved ({{ $counts['resolved'] }})
            </a>
            <a href="{{ route('admin.reports', ['status' => 'all']) }}" class="px-3 py-1.5 {{ $status == 'all' ? 'bg-[#3D2B1F] text-white' : 'bg-white text-gray-700' }} rounded-xl text-[10px] font-bold uppercase tracking-wider border border-gray-100 shadow-2xs">
                All ({{ $counts['all'] }})
            </a>
        </div>
    </div>

    {{-- ══ SELLER RISK PATTERN OVERVIEW (DECISION-SUPPORT ANALYTICS) ══ --}}
    @if(isset($topReportedSellers) && count($topReportedSellers) > 0)
    <div class="bg-[#FFFCF7] border border-[#E8DECB] rounded-3xl p-5 sm:p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-base">📊</span>
                <h3 class="text-xs font-black uppercase tracking-widest text-[#3D2B1F]">Seller Risk Pattern Overview (Decision Support)</h3>
            </div>
            <span class="text-[9px] font-bold uppercase tracking-wider text-[#A09585] bg-[#FDF8EE] px-2.5 py-1 rounded-lg border border-[#E8DECB]">
                Last 30 Days Activity
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @foreach($topReportedSellers as $item)
                @php
                    $riskBadge = match($item['risk_level']) {
                        'CRITICAL' => 'bg-red-50 text-red-700 border-red-200',
                        'HIGH'     => 'bg-amber-50 text-amber-700 border-amber-200',
                        'MEDIUM'   => 'bg-yellow-50 text-yellow-800 border-yellow-200',
                        default    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    };
                @endphp
                <div class="p-4 rounded-2xl bg-white border border-[#E8DECB] space-y-2 shadow-2xs">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <div class="text-xs font-bold text-[#1E1915] truncate">{{ $item['seller']->shopName ?: $item['seller']->name }}</div>
                            <div class="text-[9px] text-gray-500 truncate">{{ $item['seller']->email }}</div>
                        </div>
                        <span class="text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-full border {{ $riskBadge }}">
                            {{ $item['risk_level'] }} RISK
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-1.5 text-center text-[9px] py-1 border-y border-gray-50">
                        <div class="bg-[#FAF5EA] p-1 rounded">
                            <div class="font-black text-[#1E1915]">{{ $item['recent_reports'] }}</div>
                            <div class="text-gray-500 font-medium">Recent</div>
                        </div>
                        <div class="bg-red-50 p-1 rounded">
                            <div class="font-black text-red-600">{{ $item['violations'] }}</div>
                            <div class="text-red-700 font-medium">Violations</div>
                        </div>
                        <div class="bg-amber-50 p-1 rounded">
                            <div class="font-black text-amber-700">{{ $item['pending'] }}</div>
                            <div class="text-amber-800 font-medium">Pending</div>
                        </div>
                    </div>

                    <div class="text-[10px] text-gray-600 leading-snug">
                        <strong class="text-[#3D2B1F]">Rec:</strong> {{ $item['recommendation'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <p class="text-[10px] text-gray-500 italic">
            ⚖️ <strong>Safety Principle:</strong> Risk scores are strictly decision-support metrics. Administrators always make final enforcement determinations following evidence review.
        </p>
    </div>
    @endif

    {{-- Search & Filter Controls --}}
    <form method="GET" action="{{ route('admin.reports') }}" class="bg-white border border-gray-100 p-4 rounded-2xl shadow-2xs flex flex-col sm:flex-row items-center gap-3">
        <div class="relative flex-1 w-full">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by Report ID, reason, seller, customer, product..." class="w-full pl-9 pr-4 py-2 bg-gray-50/50 border border-gray-100 rounded-xl text-xs outline-none focus:border-[#C49520] transition-colors">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
            <select name="type" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50/50 border border-gray-100 rounded-xl text-xs font-bold text-gray-700 outline-none">
                <option value="all" {{ request('type') == 'all' ? 'selected' : '' }}>All Types</option>
                <option value="account" {{ request('type') == 'account' ? 'selected' : '' }}>👤 Account Reports</option>
                <option value="product" {{ request('type') == 'product' ? 'selected' : '' }}>📦 Product Reports</option>
            </select>

            <select name="severity" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50/50 border border-gray-100 rounded-xl text-xs font-bold text-gray-700 outline-none">
                <option value="all" {{ request('severity') == 'all' ? 'selected' : '' }}>All Severities</option>
                <option value="LOW" {{ request('severity') == 'LOW' ? 'selected' : '' }}>🟢 Low</option>
                <option value="MEDIUM" {{ request('severity') == 'MEDIUM' ? 'selected' : '' }}>🟡 Medium</option>
                <option value="HIGH" {{ request('severity') == 'HIGH' ? 'selected' : '' }}>🟠 High</option>
                <option value="CRITICAL" {{ request('severity') == 'CRITICAL' ? 'selected' : '' }}>🔴 Critical</option>
            </select>

            <select name="status" onchange="this.form.submit()" class="px-3 py-2 bg-gray-50/50 border border-gray-100 rounded-xl text-xs font-bold text-gray-700 outline-none">
                <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                <option value="Pending" {{ request('status', 'Pending') == 'Pending' ? 'selected' : '' }}>Pending</option>
                <option value="Under Review" {{ request('status') == 'Under Review' ? 'selected' : '' }}>Under Review</option>
                <option value="Resolved" {{ request('status') == 'Resolved' ? 'selected' : '' }}>Resolved</option>
                <option value="Dismissed" {{ request('status') == 'Dismissed' ? 'selected' : '' }}>Dismissed</option>
            </select>

            <button type="submit" class="px-4 py-2 bg-[#3D2B1F] text-white rounded-xl text-xs font-bold uppercase tracking-wider hover:bg-black transition-colors shrink-0">
                Filter
            </button>
        </div>
    </form>

    {{-- Reports List Table --}}
    <div class="bg-white border border-gray-100 rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left border-collapse min-w-180">
            <thead>
                <tr class="bg-gray-50/50 border-b border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Case ID &amp; Type</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Reporter</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Reported Party / Product</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Concern Reason</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Severity</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-700 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($reports as $report)
                @php
                    $sevBadge = match(strtoupper($report->severity ?? 'MEDIUM')) {
                        'CRITICAL' => 'bg-red-50 text-red-700 border-red-200',
                        'HIGH'     => 'bg-amber-50 text-amber-700 border-amber-200',
                        'MEDIUM'   => 'bg-yellow-50 text-yellow-800 border-yellow-200',
                        default    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    };

                    $statusBadge = match($report->status) {
                        'Pending'      => 'bg-amber-50 text-amber-700 border-amber-200',
                        'Under Review' => 'bg-blue-50 text-blue-700 border-blue-200',
                        'Resolved'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                        'Dismissed'    => 'bg-gray-50 text-gray-600 border-gray-200',
                        default        => 'bg-gray-50 text-gray-600 border-gray-200',
                    };

                    $rType = $report->reportType ?? (!empty($report->productId) ? 'product' : 'account');
                @endphp
                <tr class="group hover:bg-gray-50/50 transition-colors">
                    {{-- Case ID & Type --}}
                    <td class="px-6 py-4">
                        <div class="font-mono text-xs font-black text-[#3D2B1F]">{{ $report->getReportCode() }}</div>
                        <span class="text-[8px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full inline-block mt-0.5 {{ $rType === 'product' ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-amber-50 text-amber-800 border border-amber-100' }}">
                            {{ $rType === 'product' ? '📦 PRODUCT' : '👤 ACCOUNT' }}
                        </span>
                    </td>

                    {{-- Reporter --}}
                    <td class="px-6 py-4">
                        <div class="text-xs font-bold text-[#3D2B1F]">{{ $report->reporter->name ?? 'Anonymous' }}</div>
                        <div class="text-[9px] text-gray-500 font-medium">{{ $report->reporter->email ?? '—' }}</div>
                    </td>

                    {{-- Reported Party / Product --}}
                    <td class="px-6 py-4">
                        <div class="text-xs font-bold text-[#3D2B1F]">
                            {{ $report->reported->shopName ?: ($report->reported->name ?? 'Deleted Account') }}
                        </div>
                        @if($report->product)
                            <div class="text-[9px] text-blue-600 font-bold truncate max-w-xs">Product: {{ $report->product->name }}</div>
                        @else
                            <div class="text-[9px] text-gray-500 font-medium">Seller ID: {{ substr($report->reportedId, 0, 8) }}...</div>
                        @endif
                    </td>

                    {{-- Concern Reason --}}
                    <td class="px-6 py-4 max-w-xs">
                        <div class="text-xs font-bold text-[#3D2B1F]">{{ $report->reason }}</div>
                        <div class="text-[10px] text-gray-500 mt-0.5 line-clamp-1 italic">"{{ $report->description }}"</div>
                        @if($report->sellerResponse)
                            <span class="inline-flex items-center gap-1 text-[8px] font-bold text-purple-700 bg-purple-50 px-1.5 py-0.5 rounded border border-purple-100 mt-1">
                                ✓ Seller Responded
                            </span>
                        @endif
                    </td>

                    {{-- Severity --}}
                    <td class="px-6 py-4">
                        <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded border {{ $sevBadge }}">
                            {{ $report->severity ?? 'MEDIUM' }}
                        </span>
                    </td>

                    {{-- Status --}}
                    <td class="px-6 py-4">
                        <span class="text-[9px] font-black uppercase tracking-wider px-2 py-0.5 rounded border {{ $statusBadge }}">
                            {{ $report->status }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <button @click="openModerationModal('{{ $report->id }}')" 
                                    class="px-3 py-1.5 bg-[#3D2B1F] text-white rounded-xl text-[9px] font-black uppercase tracking-wider hover:bg-black transition-all cursor-pointer">
                                Moderate
                            </button>

                            <form action="{{ route('admin.reports.delete', $report->id) }}" method="POST" onsubmit="return confirm('Permanently delete this report record?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 transition-colors cursor-pointer" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-12 h-12 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <p class="text-xs font-bold uppercase tracking-widest">No reports found matching criteria</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $reports->appends(request()->query())->links() }}
    </div>

    {{-- ══ DETAILED MODERATION & INVESTIGATION MODAL ══ --}}
    <div x-show="showModModal"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-black/60 backdrop-blur-xs"
         style="display: none;"
         @keydown.escape.window="showModModal = false">
        
        <div class="bg-white w-full max-w-4xl rounded-3xl sm:rounded-4xl p-6 sm:p-8 shadow-2xl overflow-y-auto max-h-[92vh] border border-gray-100 flex flex-col"
             @click.stop>
            
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-[#3D2B1F] text-[#C49520] flex items-center justify-center font-bold">
                        ⚖️
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-mono font-black text-[#3D2B1F]" x-text="activeReport.reportCode"></span>
                            <span class="text-[9px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-gray-100 text-gray-700" x-text="activeReport.status"></span>
                        </div>
                        <h3 class="text-base sm:text-lg font-black text-[#3D2B1F] tracking-tight" x-text="activeReport.reason"></h3>
                    </div>
                </div>

                <button type="button" @click="showModModal = false" class="text-gray-400 hover:text-black transition-colors text-xl font-bold p-1">✕</button>
            </div>

            <!-- Modal Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 py-6 overflow-y-auto flex-1">
                
                <!-- Left: Case Details & History (7 cols) -->
                <div class="lg:col-span-7 space-y-5">
                    
                    <!-- Report Info Cards -->
                    <div class="p-4 rounded-2xl bg-gray-50 border border-gray-100 space-y-2.5 text-xs">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 block">Report Type</span>
                                <span class="font-bold text-[#3D2B1F] capitalize" x-text="activeReport.reportType + ' Report'"></span>
                            </div>
                            <div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-gray-400 block">Date Submitted</span>
                                <span class="font-bold text-[#3D2B1F]" x-text="activeReport.formattedDate"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Customer Concern Full Text -->
                    <div class="space-y-1.5">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Customer Concern Statement</label>
                        <p class="text-xs leading-relaxed p-4 rounded-2xl bg-[#FFFCF7] border border-[#E8DECB] text-[#1E1915]" x-text="activeReport.description"></p>
                    </div>

                    <!-- Evidence Previews -->
                    <template x-if="activeReport.evidence && activeReport.evidence.length > 0">
                        <div class="space-y-1.5">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Submitted Evidence</label>
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="(img, idx) in activeReport.evidence" :key="idx">
                                    <div class="rounded-xl overflow-hidden border border-gray-200 aspect-square bg-white cursor-pointer"
                                         @click="lightboxUrl = img; showLightbox = true">
                                        <img :src="img" class="w-full h-full object-cover">
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Seller Response -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-400">Seller Official Response</label>
                            <span x-show="activeReport.sellerRespondedAt" class="text-[9px] font-bold text-purple-700" x-text="'Responded ' + activeReport.sellerRespondedAt"></span>
                        </div>
                        <template x-if="activeReport.sellerResponse">
                            <div class="space-y-2">
                                <p class="text-xs leading-relaxed p-4 rounded-2xl bg-purple-50/60 border border-purple-100 text-[#1E1915]" x-text="activeReport.sellerResponse"></p>
                                <template x-if="activeReport.sellerResponseEvidence && activeReport.sellerResponseEvidence.length > 0">
                                    <div class="grid grid-cols-3 gap-2">
                                        <template x-for="(sImg, sIdx) in activeReport.sellerResponseEvidence" :key="sIdx">
                                            <div class="rounded-xl overflow-hidden border border-purple-200 aspect-square bg-white cursor-pointer"
                                                 @click="lightboxUrl = sImg; showLightbox = true">
                                                <img :src="sImg" class="w-full h-full object-cover">
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!activeReport.sellerResponse">
                            <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 text-xs text-gray-400 italic">
                                No response submitted by seller yet.
                            </div>
                        </template>
                    </div>

                    <!-- Case Timeline -->
                    <div class="space-y-2 p-4 rounded-2xl bg-gray-50/70 border border-gray-100">
                        <label class="text-[9px] font-black uppercase tracking-widest text-gray-400 block">Case History Timeline</label>
                        <div class="space-y-2 relative pl-3 border-l-2 border-[#C49520]/50 ml-1">
                            <template x-for="event in activeReport.timeline" :key="event.id">
                                <div class="text-[11px] space-y-0.5">
                                    <div class="flex items-center justify-between">
                                        <strong class="text-[#3D2B1F]" x-text="event.title"></strong>
                                        <span class="text-[9px] text-gray-400" x-text="event.date"></span>
                                    </div>
                                    <p class="text-gray-600 text-[10px]" x-text="event.description"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Right: Moderation Form & Action Dispatch (5 cols) -->
                <div class="lg:col-span-5 bg-stone-50 border border-stone-200/70 p-5 rounded-3xl space-y-4">
                    <h4 class="text-xs font-black uppercase tracking-widest text-[#3D2B1F]">Moderation Determination</h4>

                    <form :action="'/admin/reports/' + activeReport.id + '/resolve'" method="POST" class="space-y-3.5">
                        @csrf

                        {{-- Status Selection --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Case Status</label>
                            <select name="status" x-model="modStatus" required class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-[#3D2B1F] outline-none">
                                <option value="Pending">⏳ Pending</option>
                                <option value="Under Review">🔍 Under Review</option>
                                <option value="Resolved">✓ Resolved</option>
                                <option value="Dismissed">— Dismissed</option>
                            </select>
                        </div>

                        {{-- Severity Selection --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Assigned Severity</label>
                            <select name="severity" x-model="modSeverity" class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-[#3D2B1F] outline-none">
                                <option value="LOW">🟢 Low (Minor concern)</option>
                                <option value="MEDIUM">🟡 Medium (Potential policy violation)</option>
                                <option value="HIGH">🟠 High (Serious concern requiring investigation)</option>
                                <option value="CRITICAL">🔴 Critical (Severe abuse / fraud / illegal)</option>
                            </select>
                        </div>

                        {{-- Investigation Result --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Investigation Result</label>
                            <select name="investigationResult" x-model="modInvestigationResult" class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-[#3D2B1F] outline-none">
                                <option value="No Violation Found">No Violation Found</option>
                                <option value="Policy Violation Confirmed">Policy Violation Confirmed</option>
                                <option value="Insufficient Evidence">Insufficient Evidence</option>
                                <option value="Escalated for Further Investigation">Escalated for Further Investigation</option>
                            </select>
                        </div>

                        {{-- Action Taken --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Disciplinary Action</label>
                            <select name="action" x-model="modAction" class="w-full px-3.5 py-2.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-[#3D2B1F] outline-none">
                                <option value="None">None (No Action)</option>
                                <option value="Warning">Warning Issued</option>
                                <option value="Request Additional Information">Request Additional Information</option>
                                <option value="Temporary Restriction">Temporary Account Restriction</option>
                                <option value="Suspend Account">Suspend Account (Immediate Logout)</option>
                                <option value="Ban Account">Permanent Account Ban</option>
                                <option value="Escalate to Super Admin">Escalate to Super Admin</option>
                            </select>
                        </div>

                        {{-- Disciplinary Reason (Required if taking action) --}}
                        <div class="space-y-1" x-show="modAction !== 'None'">
                            <label class="text-[9px] font-black uppercase tracking-widest text-red-600">Disciplinary Justification / Notice</label>
                            <textarea name="disciplinaryReason" x-model="modDisciplinaryReason" rows="2" placeholder="Official reason shown to user / recorded in enforcement log..." class="w-full px-3.5 py-2 bg-white border border-red-200 rounded-xl text-xs outline-none resize-none"></textarea>
                        </div>

                        {{-- Internal Moderator Notes --}}
                        <div class="space-y-1">
                            <label class="text-[9px] font-black uppercase tracking-widest text-gray-500">Internal Admin Notes (Private)</label>
                            <textarea name="notes" x-model="modNotes" rows="2" placeholder="Private internal investigation notes..." class="w-full px-3.5 py-2 bg-white border border-gray-200 rounded-xl text-xs outline-none resize-none"></textarea>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full py-3 bg-[#3D2B1F] text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-black transition-all shadow-md cursor-pointer">
                                Apply Determination &amp; Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- Lightbox Viewer --}}
    <div x-show="showLightbox"
         x-cloak
         class="fixed inset-0 z-2000 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm"
         style="display: none;"
         @click="showLightbox = false"
         @keydown.escape.window="showLightbox = false">
        <div class="relative max-w-4xl max-h-[90vh] flex flex-col items-center" @click.stop>
            <img :src="lightboxUrl" class="max-w-full max-h-[80vh] rounded-2xl shadow-2xl object-contain border-2 border-white/20">
            <button type="button" @click="showLightbox = false" class="mt-4 px-5 py-2 rounded-xl text-xs font-bold uppercase tracking-widest bg-white/20 text-white hover:bg-white/40 backdrop-blur-md cursor-pointer">
                Close (ESC)
            </button>
        </div>
    </div>

</div>

<script>
function adminReportsManagement() {
    return {
        showModModal: false,
        showLightbox: false,
        lightboxUrl: '',
        activeReport: {},
        modStatus: 'Resolved',
        modSeverity: 'MEDIUM',
        modInvestigationResult: 'No Violation Found',
        modAction: 'None',
        modDisciplinaryReason: '',
        modNotes: '',

        async openModerationModal(reportId) {
            try {
                const res = await fetch(`/api/v1/reports/${reportId}`);
                if (res.ok) {
                    this.activeReport = await res.json();
                    this.modStatus = this.activeReport.status || 'Resolved';
                    this.modSeverity = this.activeReport.severity || 'MEDIUM';
                    this.modInvestigationResult = this.activeReport.investigationResult || 'No Violation Found';
                    this.modAction = this.activeReport.actionTaken || 'None';
                    this.modDisciplinaryReason = this.activeReport.disciplinaryReason || '';
                    this.modNotes = this.activeReport.adminNotes || '';
                    this.showModModal = true;
                } else {
                    alert('Could not load report details.');
                }
            } catch (e) {
                alert('Network error while loading report details.');
            }
        }
    };
}
</script>
@endsection
