@extends('layouts.superadmin')

@section('content')
<div class="space-y-8">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-widest mb-1">Security &amp; Audit Trail</div>
            <h1 class="font-serif text-3xl font-bold text-[#3D2B1F]">Audit <span class="text-[#C0422A] italic">Logs</span></h1>
            <p class="text-xs text-gray-500 mt-1">Immutable record of platform transactions, catalog modifications, and account activity.</p>
        </div>
    </div>

    <!-- Search & Filter Controls -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        {{-- Filter Tabs --}}
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('superadmin.audit-logs', ['tab' => 'all', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $tab === 'all' ? 'bg-[#3D2B1F] text-white' : 'bg-[#F7F3EE] text-gray-600 hover:bg-[#E5DDD5]' }}">
                All Events ({{ $counts['all'] }})
            </a>
            <a href="{{ route('superadmin.audit-logs', ['tab' => 'orders', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $tab === 'orders' ? 'bg-[#3D2B1F] text-white' : 'bg-[#F7F3EE] text-gray-600 hover:bg-[#E5DDD5]' }}">
                Orders ({{ $counts['orders'] }})
            </a>
            <a href="{{ route('superadmin.audit-logs', ['tab' => 'products', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $tab === 'products' ? 'bg-[#3D2B1F] text-white' : 'bg-[#F7F3EE] text-gray-600 hover:bg-[#E5DDD5]' }}">
                Products ({{ $counts['products'] }})
            </a>
            <a href="{{ route('superadmin.audit-logs', ['tab' => 'users', 'search' => $search]) }}"
               class="px-4 py-2 rounded-xl text-xs font-bold uppercase tracking-wider transition-all {{ $tab === 'users' ? 'bg-[#3D2B1F] text-white' : 'bg-[#F7F3EE] text-gray-600 hover:bg-[#E5DDD5]' }}">
                Users ({{ $counts['users'] }})
            </a>
        </div>

        {{-- Search Form --}}
        <form action="{{ route('superadmin.audit-logs') }}" method="GET" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="relative">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search actor, action, order #..."
                    class="w-64 pl-9 pr-4 py-2 bg-[#FAF7F2] border border-[#EBE3D9] text-[#3D2B1F] text-xs rounded-xl focus:outline-none focus:border-[#C0422A] transition-colors">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <button type="submit" class="px-4 py-2 bg-[#3D2B1F] hover:bg-[#C0422A] text-white text-xs font-bold rounded-xl uppercase tracking-wider transition-all cursor-pointer">
                Search
            </button>
            @if($search)
                <a href="{{ route('superadmin.audit-logs', ['tab' => $tab]) }}" class="px-3 py-2 bg-gray-100 text-gray-600 text-xs font-bold rounded-xl">Clear</a>
            @endif
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white border border-[#E5DDD5] rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-[#F7F3EE] border-b border-[#E5DDD5] text-gray-400 uppercase tracking-widest font-bold text-[9px]">
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">Event Type</th>
                        <th class="px-6 py-4">Actor</th>
                        <th class="px-6 py-4">Action</th>
                        <th class="px-6 py-4">Event Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F0EAE1]">
                    @forelse($logs as $log)
                    <tr class="hover:bg-[#FAF7F2] transition-colors">
                        <td class="px-6 py-4 text-gray-500 font-mono text-[11px] whitespace-nowrap">
                            {{ $log['time'] ? \Carbon\Carbon::parse($log['time'])->format('M d, Y · H:i:s') : '—' }}
                        </td>

                        <td class="px-6 py-4">
                            @if($log['type'] === 'order')
                                <span class="px-2 py-0.5 bg-blue-50 text-blue-700 border border-blue-200 rounded-md font-bold text-[9px] uppercase">Order</span>
                            @elseif($log['type'] === 'product')
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-md font-bold text-[9px] uppercase">Catalog</span>
                            @else
                                <span class="px-2 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 rounded-md font-bold text-[9px] uppercase">Account</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 font-bold text-[#3D2B1F]">
                            {{ $log['actor'] }}
                        </td>

                        <td class="px-6 py-4 font-medium text-gray-800">
                            {{ $log['action'] }}
                        </td>

                        <td class="px-6 py-4 text-gray-600 font-mono text-[11px]">
                            {{ $log['detail'] }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No audit records found matching your filter criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-[#E5DDD5] bg-[#FAF7F2]">
            {{ $logs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
