@extends('layouts.superadmin')

@section('content')
<div class="space-y-6" x-data="{ 
    showEnableModal: false,
    showDisableModal: false,
    typedConfirmation: '',
    messageText: @js($maintenanceMessage),
    estimatedEnd: @js($scheduledEnd ?? ''),
    isSubmitting: false
}">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="text-[10px] font-bold text-[#C0422A] uppercase tracking-[0.2em] mb-1">Developer Operations &amp; DevOps</div>
            <h1 class="font-serif text-2xl sm:text-3xl font-bold text-[#3D2B1F]">
                System <span class="text-[#C0422A] italic">Maintenance &amp; Cache</span>
            </h1>
            <p class="text-xs text-gray-500 mt-1">Control marketplace availability during schema upgrades with Super Admin lockout prevention.</p>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold rounded-2xl flex items-center gap-3 shadow-xs">
        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <span>{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="p-4 bg-red-50 border border-red-200 text-red-800 text-xs font-bold rounded-2xl flex items-center gap-3 shadow-xs">
        <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Maintenance Mode Main Card -->
        <div class="lg:col-span-2 bg-white border border-[#E5DDD5] rounded-3xl p-6 sm:p-8 shadow-xs space-y-6 flex flex-col justify-between">
            <div class="space-y-5">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                    <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">Marketplace Status</span>
                    @if($isMaintenanceMode)
                        <span class="px-3 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5 animate-pulse">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span> Maintenance Mode Active
                        </span>
                    @else
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-black uppercase tracking-wider flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Platform Live (Normal)
                        </span>
                    @endif
                </div>

                <div class="space-y-2">
                    <h3 class="font-serif text-xl font-bold text-[#3D2B1F]">
                        {{ $isMaintenanceMode ? 'Maintenance Mode is Currently ENABLED' : 'Platform is Fully Online and Operational' }}
                    </h3>
                    <p class="text-xs text-gray-500 leading-relaxed">
                        When enabled, non-superadmin visitors (customers, sellers, and regular admins) attempting to access the platform will receive an HTTP 503 Maintenance notice. Super Administrators retain full uninterrupted access.
                    </p>
                </div>

                @if($isMaintenanceMode)
                <!-- Active Maintenance Metadata -->
                <div class="p-4 bg-amber-500/10 border border-amber-500/20 rounded-2xl space-y-2.5 text-xs text-[#3D2B1F]">
                    <div class="flex items-center gap-2 font-bold text-amber-800 text-[11px] uppercase tracking-wider">
                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Active Maintenance Details</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[11px] text-gray-700">
                        <div><strong class="text-gray-900">Initiated By:</strong> {{ $enabledBy ?? 'Super Administrator' }}</div>
                        <div><strong class="text-gray-900">Started At:</strong> {{ $scheduledAt ? \Carbon\Carbon::parse($scheduledAt)->format('M d, Y h:i A') : 'N/A' }}</div>
                        @if($scheduledEnd)
                        <div><strong class="text-gray-900">Estimated End:</strong> {{ \Carbon\Carbon::parse($scheduledEnd)->format('M d, Y h:i A') }}</div>
                        @endif
                    </div>
                    <div class="pt-2 border-t border-amber-500/20 text-[11px]">
                        <strong class="text-gray-900">Notice to Visitors:</strong>
                        <p class="text-gray-600 mt-0.5 italic">"{{ $maintenanceMessage }}"</p>
                    </div>
                </div>

                <!-- Disable Maintenance Form -->
                <form action="{{ route('superadmin.maintenance.toggle') }}" method="POST" class="pt-2">
                    @csrf
                    <input type="hidden" name="enable" value="0">
                    <button type="button" 
                            @click="showDisableModal = true"
                            class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all cursor-pointer shadow-sm hover:shadow-md flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Bring Platform Back Online</span>
                    </button>
                </form>
                @else
                <!-- Enable Maintenance Button (Opens Confirmation Modal) -->
                <div class="pt-2">
                    <button type="button" 
                            @click="showEnableModal = true; typedConfirmation = '';"
                            class="w-full py-3.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs uppercase tracking-widest rounded-xl transition-all cursor-pointer shadow-sm hover:shadow-md flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>Initiate System Maintenance Mode</span>
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Lockout Prevention & Cache Utilities Card -->
        <div class="bg-white border border-[#E5DDD5] rounded-3xl p-6 sm:p-8 shadow-xs space-y-5">
            <div class="border-b border-gray-100 pb-3">
                <span class="text-[10px] font-black uppercase tracking-widest text-[#C0422A]">Security Protocols</span>
                <h3 class="font-serif text-base font-bold text-[#3D2B1F] mt-1">Lockout Prevention</h3>
            </div>

            <div class="space-y-3 text-xs text-gray-600 leading-relaxed">
                <div class="flex items-start gap-2.5">
                    <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">✓</div>
                    <p><strong>Super Admin Bypass:</strong> Super Admins always bypass maintenance via role validation.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">✓</div>
                    <p><strong>Logout Lock:</strong> Sign Out is disabled during maintenance to prevent orphaned sessions.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <div class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold shrink-0 mt-0.5">✓</div>
                    <p><strong>Session Keep-Alive:</strong> Background heartbeat pings keep your Super Admin session active.</p>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100">
                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 block mb-2">DevOps Utility</span>
                <form action="{{ route('superadmin.clear-cache') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold text-[11px] uppercase tracking-wider rounded-xl transition-all cursor-pointer flex items-center justify-center gap-2">
                        <svg class="w-3.5 h-3.5 text-[#C0422A]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        <span>Purge All Server Caches</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: ENABLE MAINTENANCE CONFIRMATION     -->
    <!-- ========================================== -->
    <div x-show="showEnableModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-200000 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none; z-index: 200000;"
         @keydown.escape.window="if(!isSubmitting) showEnableModal = false"
         role="dialog"
         aria-modal="true"
         aria-labelledby="enable-maintenance-title"
         x-cloak>
        
        <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border border-gray-100 space-y-5"
             @click.stop>
            <!-- Modal Header -->
            <div class="flex items-center gap-3 border-b border-gray-100 pb-4">
                <div class="w-10 h-10 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h3 id="enable-maintenance-title" class="font-serif text-lg font-bold text-gray-900">Enable System Maintenance</h3>
                    <p class="text-[11px] text-gray-500 font-medium">Verify platform locking parameters before proceeding.</p>
                </div>
            </div>

            <!-- Warning Callout -->
            <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl text-xs space-y-1.5 leading-relaxed">
                <div class="font-bold flex items-center gap-1.5">
                    <span>⚠️ Immediate Platform Impact:</span>
                </div>
                <ul class="list-disc pl-4 space-y-1 text-[11px] text-amber-800">
                    <li>All customers, sellers, and regular admins will be <strong>blocked immediately</strong> (HTTP 503).</li>
                    <li>Active checkouts, browsing sessions, and background operations will be paused.</li>
                    <li><strong>Super Admin Lockout Prevention:</strong> Your Sign Out button will be disabled until maintenance mode is turned off.</li>
                </ul>
            </div>

            <!-- Form -->
            <form action="{{ route('superadmin.maintenance.toggle') }}" method="POST" class="space-y-4" @submit="isSubmitting = true">
                @csrf
                <input type="hidden" name="enable" value="1">

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Notice Message for Visitors</label>
                    <textarea name="message" x-model="messageText" rows="2" class="w-full p-3 bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-xl focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all" placeholder="Enter message..."></textarea>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1.5">Estimated Completion Time (Optional)</label>
                    <input type="datetime-local" name="estimated_end" x-model="estimatedEnd" class="w-full p-2.5 bg-gray-50 border border-gray-200 text-gray-900 text-xs rounded-xl focus:bg-white focus:outline-none focus:border-[#C0422A] transition-all">
                </div>

                <!-- Typed Confirmation -->
                <div class="p-3.5 bg-gray-50 border border-gray-200 rounded-2xl space-y-2">
                    <label class="block text-[10px] font-black text-gray-700 uppercase tracking-widest">
                        Type <span class="text-[#C0422A] font-mono select-all">MAINTENANCE</span> to confirm:
                    </label>
                    <input type="text" 
                           name="confirmation" 
                           x-model="typedConfirmation" 
                           autocomplete="off"
                           placeholder="Type MAINTENANCE here"
                           class="w-full p-2.5 bg-white border border-gray-300 rounded-xl text-xs font-mono font-bold tracking-wider text-gray-900 focus:outline-none focus:border-[#C0422A]">
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" 
                            @click="showEnableModal = false"
                            :disabled="isSubmitting"
                            class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl uppercase tracking-wider transition-colors cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit" 
                            :disabled="typedConfirmation.trim() !== 'MAINTENANCE' || isSubmitting"
                            class="px-5 py-2.5 bg-[#C0422A] hover:bg-[#A33520] disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold text-xs rounded-xl uppercase tracking-wider transition-all shadow-md flex items-center gap-2 cursor-pointer">
                        <span x-show="isSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                        <span x-text="isSubmitting ? 'Enabling...' : 'Confirm & Enable'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================== -->
    <!-- MODAL: DISABLE MAINTENANCE CONFIRMATION    -->
    <!-- ========================================== -->
    <div x-show="showDisableModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-200000 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
         style="display: none; z-index: 200000;"
         @keydown.escape.window="showDisableModal = false"
         role="dialog"
         aria-modal="true"
         x-cloak>
        
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-gray-100 space-y-5"
             @click.stop>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <h3 class="font-serif text-lg font-bold text-gray-900">Bring Platform Back Online</h3>
                    <p class="text-[11px] text-gray-500 font-medium">Re-enable public marketplace and customer transactions.</p>
                </div>
            </div>

            <p class="text-xs text-gray-600 leading-relaxed">
                This will immediately clear the maintenance block. All customers, sellers, and artisans will be able to browse, log in, and transact normally.
            </p>

            <form action="{{ route('superadmin.maintenance.toggle') }}" method="POST" class="flex items-center justify-end gap-3 pt-2">
                @csrf
                <input type="hidden" name="enable" value="0">
                <button type="button" 
                        @click="showDisableModal = false"
                        class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl uppercase tracking-wider transition-colors cursor-pointer">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl uppercase tracking-wider transition-all shadow-md cursor-pointer">
                    Confirm &amp; Go Live
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
