@extends('layouts.admin')
@section('title', 'System Settings')

@section('content')
<div class="space-y-8">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold text-(--charcoal) font-serif">System Settings</h1>
        <p class="text-sm text-(--muted) mt-1">Configure global platform behavior and defaults.</p>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-8">
        @csrf

        {{-- General --}}
        <div class="bg-white rounded-2xl border border-(--border) p-6 space-y-5">
            <h2 class="text-xs font-bold uppercase tracking-widest text-(--muted) pb-3 border-b border-(--border)">General</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-(--charcoal) mb-1.5 uppercase tracking-wider">Site Name</label>
                    <input type="text" name="site_name" value="{{ $settings['site_name'] ?? '' }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-(--border) text-sm focus:outline-none focus:ring-2 focus:ring-(--rust)/20 focus:border-(--rust) bg-(--cream)">
                </div>
                <div>
                    <label class="block text-xs font-bold text-(--charcoal) mb-1.5 uppercase tracking-wider">Support Email</label>
                    <input type="email" name="support_email" value="{{ $settings['support_email'] ?? '' }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-(--border) text-sm focus:outline-none focus:ring-2 focus:ring-(--rust)/20 focus:border-(--rust) bg-(--cream)">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-(--charcoal) mb-1.5 uppercase tracking-wider">Site Tagline</label>
                    <input type="text" name="site_tagline" value="{{ $settings['site_tagline'] ?? '' }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-(--border) text-sm focus:outline-none focus:ring-2 focus:ring-(--rust)/20 focus:border-(--rust) bg-(--cream)">
                </div>
            </div>
        </div>

        {{-- Commerce --}}
        <div class="bg-white rounded-2xl border border-(--border) p-6 space-y-5">
            <h2 class="text-xs font-bold uppercase tracking-widest text-(--muted) pb-3 border-b border-(--border)">Commerce</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-bold text-(--charcoal) mb-1.5 uppercase tracking-wider">Commission Rate (%)</label>
                    <input type="number" name="commission_rate" value="{{ $settings['commission_rate'] ?? 10 }}" min="0" max="100" step="0.1"
                        class="w-full px-4 py-2.5 rounded-xl border border-(--border) text-sm focus:outline-none focus:ring-2 focus:ring-(--rust)/20 focus:border-(--rust) bg-(--cream)">
                </div>
                <div>
                    <label class="block text-xs font-bold text-(--charcoal) mb-1.5 uppercase tracking-wider">Min. Withdrawal (₱)</label>
                    <input type="number" name="min_withdrawal" value="{{ $settings['min_withdrawal'] ?? 500 }}" min="0"
                        class="w-full px-4 py-2.5 rounded-xl border border-(--border) text-sm focus:outline-none focus:ring-2 focus:ring-(--rust)/20 focus:border-(--rust) bg-(--cream)">
                </div>
                <div>
                    <label class="block text-xs font-bold text-(--charcoal) mb-1.5 uppercase tracking-wider">Max Banners per Seller</label>
                    <input type="number" name="max_banner_per_seller" value="{{ $settings['max_banner_per_seller'] ?? 3 }}" min="1" max="10"
                        class="w-full px-4 py-2.5 rounded-xl border border-(--border) text-sm focus:outline-none focus:ring-2 focus:ring-(--rust)/20 focus:border-(--rust) bg-(--cream)">
                </div>
            </div>
        </div>

        {{-- Access Control --}}
        <div class="bg-white rounded-2xl border border-(--border) p-6 space-y-5">
            <h2 class="text-xs font-bold uppercase tracking-widest text-(--muted) pb-3 border-b border-(--border)">Access Control</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                @foreach(['allow_registration' => 'Allow Customer Registration', 'allow_seller_signup' => 'Allow Seller Sign-Up'] as $key => $label)
                <div class="flex items-center justify-between p-4 rounded-xl border border-(--border) bg-(--cream)">
                    <div>
                        <p class="text-sm font-semibold text-(--charcoal)">{{ $label }}</p>
                        <p class="text-xs text-(--muted)">Toggle to open/close {{ strtolower($label) }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="{{ $key }}" value="0">
                        <input type="checkbox" name="{{ $key }}" value="1" class="sr-only peer" {{ ($settings[$key] ?? '1') == '1' ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-(--rust)/30 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-(--rust)"></div>
                    </label>
                </div>
                @endforeach
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="px-8 py-3 bg-(--rust) text-white text-sm font-bold rounded-xl hover:opacity-90 transition-all uppercase tracking-widest">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
