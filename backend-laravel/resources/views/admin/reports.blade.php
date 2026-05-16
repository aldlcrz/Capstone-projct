@extends('layouts.admin')

@section('content')
<div class="space-y-8" x-data="{ 
    showResolveModal: false, 
    resolvingReport: { id: '', reason: '', description: '' } 
}">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-[#3D2B1F] tracking-tight">System Reports</h2>
            <p class="text-xs text-gray-500 mt-1 uppercase tracking-widest font-bold">Review and moderate platform disputes</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.reports', ['status' => 'Pending']) }}" class="px-4 py-2 {{ $status == 'Pending' ? 'bg-[#3D2B1F] text-white' : 'bg-white text-gray-600' }} rounded-xl text-[10px] font-bold uppercase tracking-widest border border-gray-100 transition-all">
                Pending ({{ $counts['pending'] }})
            </a>
            <a href="{{ route('admin.reports', ['status' => 'Resolved']) }}" class="px-4 py-2 {{ $status == 'Resolved' ? 'bg-[#3D2B1F] text-white' : 'bg-white text-gray-600' }} rounded-xl text-[10px] font-bold uppercase tracking-widest border border-gray-100 transition-all">
                Resolved ({{ $counts['resolved'] }})
            </a>
            <a href="{{ route('admin.reports', ['status' => 'all']) }}" class="px-4 py-2 {{ $status == 'all' ? 'bg-[#3D2B1F] text-white' : 'bg-white text-gray-600' }} rounded-xl text-[10px] font-bold uppercase tracking-widest border border-gray-100 transition-all">
                All Reports
            </a>
        </div>
    </div>

    {{-- Reports List --}}
    <div class="bg-white border border-gray-100 rounded-3xl overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50/50 border-bottom border-gray-100">
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Reporter</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Reported</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Issue</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Date</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($reports as $report)
                <tr class="group hover:bg-gray-50/50 transition-colors">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-rust/10 flex items-center justify-center text-[10px] font-bold text-rust">
                                {{ substr($report->reporter->name ?? '?', 0, 1) }}
                            </div>
                            <div>
                                <div class="text-[11px] font-black text-[#3D2B1F] uppercase tracking-wide">{{ $report->reporter->name ?? 'Deleted User' }}</div>
                                <div class="text-[9px] text-gray-400 font-bold uppercase">{{ $report->type == 'CustomerReportingSeller' ? 'Customer' : 'Seller' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-[11px] font-black text-gray-600 uppercase tracking-wide">{{ $report->reported->name ?? 'Deleted User' }}</div>
                        <div class="text-[9px] text-gray-400 font-bold uppercase">ID: {{ substr($report->reportedId, 0, 8) }}...</div>
                    </td>
                    <td class="px-6 py-4 max-w-xs">
                        <div class="text-[11px] font-bold text-[#3D2B1F]">{{ $report->reason }}</div>
                        <div class="text-[10px] text-gray-500 mt-1 line-clamp-2 italic">"{{ $report->description }}"</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-[10px] font-bold text-gray-500">{{ $report->createdAt->format('M d, Y') }}</div>
                        <div class="text-[9px] text-gray-400 uppercase font-black">{{ $report->createdAt->format('h:i A') }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 {{ $report->status == 'Pending' ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }} rounded text-[9px] font-black uppercase tracking-widest">
                            {{ $report->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @if($report->status == 'Pending')
                            <button @click="resolvingReport = { id: '{{ $report->id }}', reason: '{{ $report->reason }}', description: '{{ $report->description }}' }; showResolveModal = true" class="px-3 py-1.5 bg-rust text-white rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-[#C0422A] transition-all">
                                Resolve
                            </button>
                            @endif
                            <form action="{{ route('admin.reports.delete', $report->id) }}" method="POST" onsubmit="return confirm('Permanently delete this report record?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-12 h-12 text-gray-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            <p class="text-xs font-bold uppercase tracking-widest">No reports found for this filter</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $reports->appends(['status' => $status])->links() }}
    </div>

    {{-- Resolve Modal --}}
    <div x-show="showResolveModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" x-cloak>
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] p-8 shadow-2xl" @click.away="showResolveModal = false">
            <h3 class="text-xl font-black text-[#3D2B1F] mb-2 uppercase tracking-tight">Resolve Dispute</h3>
            <div class="bg-rust/5 p-4 rounded-2xl mb-6">
                <p class="text-[10px] font-bold text-rust uppercase tracking-widest mb-1">Issue reported:</p>
                <p class="text-xs font-bold text-[#3D2B1F]" x-text="resolvingReport.reason"></p>
                <p class="text-[11px] text-gray-500 mt-2" x-text="resolvingReport.description"></p>
            </div>

            <form :action="'/admin/reports/' + resolvingReport.id + '/resolve'" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Action Taken</label>
                    <select name="action" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rust/20">
                        <option value="Warning Sent">Warning Sent</option>
                        <option value="User Blocked">User Blocked</option>
                        <option value="Product Removed">Product Removed</option>
                        <option value="No Action Necessary">No Action Necessary</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block mb-2">Resolution Notes</label>
                    <textarea name="notes" rows="3" placeholder="Explain the outcome of this report..." class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-rust/20"></textarea>
                </div>
                <div class="flex gap-3 pt-4">
                    <button type="button" @click="showResolveModal = false" class="flex-1 px-6 py-4 bg-gray-100 text-gray-700 rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">Cancel</button>
                    <button type="submit" class="flex-1 px-6 py-4 bg-[#3D2B1F] text-white rounded-2xl text-[10px] font-bold uppercase tracking-widest hover:bg-rust transition-all">Confirm Resolution</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
