<?php

use Livewire\Component;
use App\Services\WebsiteApi;
use Illuminate\Support\Str;
use Carbon\Carbon;

new class extends Component {
    public $confirmingLogout = false;
    public $password = '';
    public $sessions = [];

    public function mount()
    {
        $this->loadSessions();
    }

    public function loadSessions()
    {
        $response = WebsiteApi::client()->get('/user/browser-sessions');

        if ($response->successful()) {
            $this->sessions = $response->json() ?? [];
        }
    }

    public function confirmLogout()
    {
        $this->password = '';
        $this->resetErrorBag();
        $this->confirmingLogout = true;
    }

    public function logoutOtherBrowserSessions()
    {
        $this->validate([
            'password' => 'required|string',
        ]);

        $response = WebsiteApi::client()->post('/user/other-browser-sessions', [
            'password' => $this->password,
        ]);

        if ($response->successful()) {
            $this->confirmingLogout = false;
            $this->password = '';
            $this->loadSessions();
            $this->dispatch('logged-out');
            session()->flash('status', 'Other browser sessions logged out.');
        } else {
            $this->addError('password', $response->json('message') ?? 'This password does not match our records.');
        }
    }
};
?>

<x-action-section>
    <x-slot name="title">
        {{ __('Browser Sessions') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Manage and log out your active sessions on other browsers and devices.') }}
    </x-slot>

    <x-slot name="content">
        <div class="max-w-xl text-sm text-gray-600">
            {{ __('If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive.') }}
        </div>

        {{-- Active Sessions List --}}
        @if (count($sessions) > 0)
        <div class="mt-6 space-y-5">
            @foreach ($sessions as $session)
            @php
            $ua = strtolower($session['user_agent'] ?? '');
            $isMobile = Str::contains($ua, ['mobile', 'android', 'iphone', 'ipad']);
            $lastActiveTime = !empty($session['last_activity'])
            ? Carbon::createFromTimestamp($session['last_activity'])->diffForHumans()
            : __('Recent');
            @endphp

            <div class="flex items-center gap-4 p-3 bg-slate-50/80 rounded-2xl border border-slate-100">
                <div class="size-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center shrink-0 shadow-sm text-slate-500">
                    @if ($isMobile)
                    {{-- Phone Icon --}}
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                    </svg>
                    @else
                    {{-- Desktop Icon --}}
                    <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                    </svg>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    <div class="text-xs font-bold text-slate-800 truncate">
                        {{ Str::limit($session['user_agent'] ?? 'Unknown Device', 50) }}
                    </div>
                    <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-2">
                        <span>{{ $session['ip_address'] ?? 'Unknown IP' }}</span>
                        <span>&bull;</span>
                        <span class="text-slate-500 font-medium">{{ __('Last active') }} {{ $lastActiveTime }}</span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Action Button --}}
        <div class="flex items-center gap-3 mt-6">
            <x-button type="button" wire:click="confirmLogout" wire:loading.attr="disabled">
                {{ __('Log Out Other Browser Sessions') }}
            </x-button>

            <x-action-message class="text-xs font-semibold text-emerald-600" on="logged-out">
                {{ __('Done.') }}
            </x-action-message>
        </div>

        {{-- Bulletproof Teleported Modal --}}
        <div
            x-data="{ show: @entangle('confirmingLogout') }"
            x-show="show"
            x-teleport="body"
            class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
            style="display: none;">
            {{-- Dark Blurred Backdrop --}}
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
                @click="show = false"></div>

            {{-- Solid White Modal Card --}}
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative bg-white rounded-3xl shadow-2xl border border-slate-100 max-w-lg w-full p-6 sm:p-8 z-10 overflow-hidden">
                {{-- Gold Header Accent --}}
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-[#D4AF37] to-transparent"></div>

                <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">
                    {{ __('Log Out Other Browser Sessions') }}
                </h3>

                <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                    {{ __('Please enter your password to confirm you would like to log out of your other browser sessions across all of your devices.') }}
                </p>

                <div class="mt-5">
                    <x-input
                        type="password"
                        class="block w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 text-sm focus:border-[#D4AF37] focus:ring-1 focus:ring-[#D4AF37]"
                        placeholder="{{ __('Enter your current password') }}"
                        wire:model="password"
                        wire:keydown.enter="logoutOtherBrowserSessions" />

                    <x-input-error for="password" class="mt-2" />
                </div>

                <div class="mt-6 flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <x-secondary-button type="button" @click="show = false" wire:loading.attr="disabled">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-button
                        type="button"
                        wire:click="logoutOtherBrowserSessions"
                        wire:loading.attr="disabled"
                        class="bg-[#D4AF37] hover:bg-[#c49f2e] text-slate-900 font-black">
                        {{ __('Confirm Logout') }}
                    </x-button>
                </div>
            </div>
        </div>
    </x-slot>
</x-action-section>