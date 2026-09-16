@extends('layouts.superadmin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Security &amp; Forensic Trail</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#3D2B1F]">
                Audit <span class="text-[#C0422A] italic">Logs</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Immutable trail of platform actions, order status changes, catalog edits, and user account events.</p>
        </div>
    </div>

    <!-- Filter Tabs Bar -->
    <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
        <a href="{{ route('superadmin.audit-logs', ['tab' => 'all', 'search' => $search]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 {{ $tab === 'all' ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400' }}">
            <span>All Events</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tab === 'all' ? 'bg-[#C0422A] text-white' : 'bg-gray-100 text-gray-600 font-black' }}">{{ $counts['all'] }}</span>
        </a>
        <a href="{{ route('superadmin.audit-logs', ['tab' => 'orders', 'search' => $search]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 {{ $tab === 'orders' ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400' }}">
            <span>Orders</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tab === 'orders' ? 'bg-[#C0422A] text-white' : 'bg-gray-100 text-gray-600 font-black' }}">{{ $counts['orders'] }}</span>
        </a>
        <a href="{{ route('superadmin.audit-logs', ['tab' => 'products', 'search' => $search]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 {{ $tab === 'products' ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400' }}">
            <span>Catalog</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tab === 'products' ? 'bg-[#C0422A] text-white' : 'bg-gray-100 text-gray-600 font-black' }}">{{ $counts['products'] }}</span>
        </a>
        <a href="{{ route('superadmin.audit-logs', ['tab' => 'users', 'search' => $search]) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0 flex items-center gap-2 {{ $tab === 'users' ? 'bg-[#3D2B1F] text-white shadow-sm' : 'bg-white border border-[#E5DDD5] text-gray-600 hover:border-gray-400' }}">
            <span>Accounts</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] {{ $tab === 'users' ? 'bg-[#C0422A] text-white' : 'bg-gray-100 text-gray-600 font-black' }}">{{ $counts['users'] }}</span>
        </a>
    </div>

    <!-- Search Bar -->
    <div class="bg-white border border-[#E5DDD5] rounded-2xl p-3 sm:p-4 shadow-xs">
        <form action="{{ route('superadmin.audit-logs') }}" method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative flex-1 w-full">
                <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search actor, action, order #, details..."
                    class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all">
            </div>
            <div class="flex items-center gap-2 w-full sm:w-auto">
                <button type="submit" class="flex-1 sm:flex-none px-5 py-2.5 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold rounded-xl transition-all shadow-xs cursor-pointer">
                    Search
                </button>
                @if($search)
                    <a href="{{ route('superadmin.audit-logs', ['tab' => $tab]) }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-bold rounded-xl transition-all cursor-pointer">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl overflow-hidden shadow-xs">
        <div class="overflow-x-auto no-scrollbar">
            <table class="w-full text-left border-collapse min-w-175">
                <thead>
                    <tr class="bg-gray-50/70 border-b border-[#E5DDD5]">
                        <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Timestamp</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Category</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Actor</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Action Performed</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-600 uppercase tracking-widest">Event Context</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-amber-50/20 transition-colors">
                        <td class="px-6 py-4 text-gray-500 font-mono text-[11px] whitespace-nowrap">
                            {{ $log['time'] ? \Carbon\Carbon::parse($log['time'])->format('M d, Y · h:i:s A') : '—' }}
                        </td>

                        <td class="px-6 py-4">
                            @if($log['type'] === 'order')
                                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 border border-blue-200 rounded-full font-black text-[10px] uppercase tracking-wider">Order</span>
                            @elseif($log['type'] === 'product')
                                <span class="px-2.5 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full font-black text-[10px] uppercase tracking-wider">Catalog</span>
                            @else
                                <span class="px-2.5 py-1 bg-purple-50 text-purple-700 border border-purple-200 rounded-full font-black text-[10px] uppercase tracking-wider">Account</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 font-bold text-gray-900 text-xs">
                            {{ $log['actor'] }}
                        </td>

                        <td class="px-6 py-4 font-semibold text-gray-800 text-xs">
                            {{ $log['action'] }}
                        </td>

                        <td class="px-6 py-4 text-gray-600 font-mono text-[11px] max-w-md truncate">
                            {{ $log['detail'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                            <div class="w-12 h-12 bg-gray-50 rounded-2xl flex items-center justify-center mx-auto mb-3 border border-gray-100">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            </div>
                            <div class="text-sm font-bold text-gray-700">No Audit Records</div>
                            <p class="text-xs text-gray-400 mt-0.5">No log entries match your filter criteria.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="p-6 bg-gray-50/50 border-t border-[#E5DDD5]">
            {{ $logs->appends(['tab' => $tab, 'search' => $search])->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
