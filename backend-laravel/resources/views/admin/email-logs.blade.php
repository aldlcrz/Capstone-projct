@extends('layouts.admin')

@section('title', 'Email Notification History')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight">Email Notification History</h1>
            <p class="text-xs font-semibold text-gray-500">Log of automated Gmail notifications dispatched to Customers and Sellers.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 border-b border-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-400">
                        <th class="px-6 py-4">Recipient</th>
                        <th class="px-6 py-4">Notification Type</th>
                        <th class="px-6 py-4">Subject</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4">Related Entity</th>
                        <th class="px-6 py-4">Sent At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-bold text-gray-900">
                                {{ $log->recipient_email }}
                                @if($log->user)
                                    <span class="block text-[10px] font-medium text-gray-400">({{ $log->user->name }} - {{ ucfirst($log->user->role) }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-700">
                                <span class="px-2.5 py-1 bg-gray-100 rounded-lg text-[10px] font-bold text-gray-600 uppercase">
                                    {{ str_replace('_', ' ', $log->notification_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 font-medium max-w-xs truncate">
                                {{ $log->subject }}
                            </td>
                            <td class="px-6 py-4 font-bold">
                                @if($log->delivery_status === 'sent')
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 rounded-lg text-[10px]">SUCCESS</span>
                                @else
                                    <span class="px-2.5 py-1 bg-rose-50 text-rose-700 rounded-lg text-[10px]" title="{{ $log->error_message }}">FAILED</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                @if($log->related_type && $log->related_id)
                                    <span class="font-mono text-[11px]">{{ $log->related_type }} #{{ substr($log->related_id, 0, 8) }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-400 font-medium">
                                {{ $log->sent_at ? $log->sent_at->format('M d, Y h:i A') : $log->created_at->format('M d, Y h:i A') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-400 font-medium">
                                No email logs recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
