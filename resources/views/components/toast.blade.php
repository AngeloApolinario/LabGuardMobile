@php
$initialToast = session('toast');

// Also catch status and email verification redirects automatically
if (! $initialToast && session('status')) {
$status = session('status');
$msg = match($status) {
'profile-information-updated' => 'Profile information updated successfully.',
'two-factor-authentication-enabled' => 'Two-factor authentication has been enabled.',
'two-factor-authentication-disabled' => 'Two-factor authentication has been disabled.',
default => $status
};
$initialToast = ['type' => 'success', 'title' => 'Success', 'message' => $msg];
} elseif (! $initialToast && session('verified_toast')) {
$initialToast = ['type' => 'success', 'title' => 'Identity Confirmed', 'message' => session('verified_toast')];
}

$initType = is_array($initialToast) ? ($initialToast['type'] ?? 'success') : 'success';
$initTitle = is_array($initialToast) ? ($initialToast['title'] ?? 'Success') : 'Success';
$initMessage = is_array($initialToast) ? ($initialToast['message'] ?? '') : ($initialToast ?? '');
@endphp

<div x-data="{
    show: {{ ($initialToast) ? 'true' : 'false' }},
    type: '{{ $initType }}',
    title: '{{ $initTitle }}',
    message: '{{ $initMessage }}',
    timeout: null,

    trigger(detail) {
        // Unpack Livewire 3/4 array wrappers:
        let data = Array.isArray(detail) ? (detail[0] || {}) : (detail || {});

        this.type = (data.type === 'error') ? 'danger' : (data.type || 'success');
        this.title = data.title || (this.type === 'danger' ? 'Error' : 'Success');
        this.message = data.message || '';
        this.show = true;

        clearTimeout(this.timeout);
        this.timeout = setTimeout(() => this.show = false, 5000);
    },

    getStyle() {
        const styles = {
            'success': { border: 'border-emerald-500', icon: 'bg-emerald-500/20 text-emerald-400', title: 'text-[#D4AF37]' },
            'danger':  { border: 'border-rose-500',    icon: 'bg-rose-500/20 text-rose-400',       title: 'text-rose-400' },
            'warning': { border: 'border-[#D4AF37]',   icon: 'bg-[#D4AF37]/20 text-[#D4AF37]',   title: 'text-[#D4AF37]' }
        };
        return styles[this.type] || { border: 'border-blue-500', icon: 'bg-blue-500/20 text-blue-400', title: 'text-blue-400' };
    }
}"
    x-init="if (show) timeout = setTimeout(() => show = false, 5000)"
    @toast.window="trigger($event.detail)"
    @saved.window="trigger({ title: 'Changes Saved', message: 'Your changes have been saved successfully.', type: 'success' })"
    @logged-out.window="trigger({ title: 'Sessions Cleared', message: 'Other devices have been logged out.', type: 'success' })"
    x-show="show"
    x-cloak
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 -translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-4"
    class="fixed top-5 left-4 right-4 sm:left-auto sm:right-6 sm:w-96 z-[300] max-w-md bg-slate-900 border text-white p-4 rounded-2xl shadow-2xl flex items-start gap-3 backdrop-blur-md"
    :class="getStyle().border"
    style="display: none;">

    {{-- Icon with dynamic symbol --}}
    <div class="size-8 rounded-xl border border-current/30 flex items-center justify-center shrink-0 mt-0.5"
        :class="getStyle().icon">
        <template x-if="type === 'success'">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
            </svg>
        </template>
        <template x-if="type === 'danger'">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </template>
        <template x-if="type !== 'success' && type !== 'danger'">
            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </template>
    </div>

    {{-- Title & Message --}}
    <div class="flex-1 min-w-0">
        <h4 class="text-xs font-black uppercase tracking-widest" :class="getStyle().title" x-text="title"></h4>
        <p class="text-xs font-semibold text-slate-300 mt-0.5 leading-relaxed" x-text="message"></p>
    </div>

    {{-- Manual Close Button --}}
    <button @click="show = false" class="text-slate-500 hover:text-white transition-colors focus:outline-none p-0.5">
        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>
</div>