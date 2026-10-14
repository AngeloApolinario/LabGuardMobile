<?php

use Livewire\Component;
use App\Services\WebsiteApi;

new class extends Component {
    public $showingQrCode = false;
    public $showingConfirmation = false;
    public $showingRecoveryCodes = false;
    public $code = '';
    public $qrCodeSvg = '';
    public $setupKey = '';
    public $recoveryCodes = [];
    public $errorMessage = '';

    public function getEnabledProperty(): bool
    {
        return !empty(session('user.two_factor_confirmed_at') ?? session('user.two_factor_secret'));
    }

    public function enableTwoFactorAuthentication()
    {
        $this->resetErrorBag();
        $this->errorMessage = '';

        // 1. Tell website to generate 2FA secret
        $response = WebsiteApi::client()->post('/user/two-factor-authentication');

        if (! $response->successful()) {
            $data = $response->json();
            $this->errorMessage = $data['message'] ?? 'Unable to enable 2FA on the server.';
            return;
        }

        // 2. Fetch the QR code SVG and setup key
        $this->showQrCode();
    }

    public function showQrCode()
    {
        $response = WebsiteApi::client()->get('/user/two-factor-qr-code');

        if ($response->successful()) {
            $this->qrCodeSvg = $response->json('svg');
            $this->setupKey = $response->json('setupKey');
            $this->showingQrCode = true;
            $this->showingConfirmation = true;

            // Mark secret active in local session so UI updates
            $user = session('user', []);
            $user['two_factor_secret'] = 'pending';
            session(['user' => $user]);
        } else {
            $data = $response->json();
            $this->errorMessage = $data['message'] ?? 'Unable to load QR Code from server.';
        }
    }

    public function confirmTwoFactorAuthentication()
    {
        $this->validate(['code' => 'required|string']);

        $response = WebsiteApi::client()->post('/user/confirmed-two-factor-authentication', [
            'code' => $this->code,
        ]);

        if ($response->successful()) {
            $this->showingQrCode = false;
            $this->showingConfirmation = false;
            $this->showRecoveryCodes();

            // Refresh user session completely from server
            $userRes = WebsiteApi::client()->get('/user/profile');
            if ($userRes->successful()) {
                session(['user' => $userRes->json('user') ?? $userRes->json()]);
            }
        } else {
            $this->addError('code', 'The provided 6-digit code was invalid. Try again.');
        }
    }

    public function showRecoveryCodes()
    {
        $response = WebsiteApi::client()->get('/user/two-factor-recovery-codes');
        if ($response->successful()) {
            $this->recoveryCodes = $response->json() ?? [];
            $this->showingRecoveryCodes = true;
        }
    }

    public function regenerateRecoveryCodes()
    {
        $response = WebsiteApi::client()->post('/user/two-factor-recovery-codes');
        if ($response->successful()) {
            $this->showRecoveryCodes();
        }
    }

    public function disableTwoFactorAuthentication()
    {
        $response = WebsiteApi::client()->delete('/user/two-factor-authentication');

        if ($response->successful()) {
            $this->showingQrCode = false;
            $this->showingConfirmation = false;
            $this->showingRecoveryCodes = false;
            $this->qrCodeSvg = '';
            $this->setupKey = '';

            $user = session('user', []);
            unset($user['two_factor_secret'], $user['two_factor_confirmed_at']);
            session(['user' => $user]);
        }
    }
};
?>

<x-action-section>
    <x-slot name="title">
        {{ __('Two Factor Authentication') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Add additional security to your account using two factor authentication.') }}
    </x-slot>

    <x-slot name="content">
        {{-- Server Error Alert --}}
        @if ($errorMessage)
        <div class="mb-4 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 text-xs font-semibold">
            {{ $errorMessage }}
        </div>
        @endif

        <h3 class="text-lg font-medium text-gray-900">
            @if ($this->enabled)
            @if ($showingConfirmation)
            {{ __('Finish enabling two factor authentication.') }}
            @else
            {{ __('You have enabled two factor authentication.') }}
            @endif
            @elseif ($showingQrCode)
            {{ __('Finish enabling two factor authentication.') }}
            @else
            {{ __('You have not enabled two factor authentication.') }}
            @endif
        </h3>

        <div class="mt-3 max-w-xl text-sm text-gray-600">
            <p>
                {{ __('When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s Google Authenticator application.') }}
            </p>
        </div>

        {{-- QR Code & Setup Key Section (Shows whenever QR code is requested) --}}
        @if ($showingQrCode)
        <div class="mt-4 max-w-xl text-sm text-gray-600">
            <p class="font-semibold text-slate-800">
                {{ __('Scan this QR code using Google Authenticator or enter the setup key manually:') }}
            </p>
        </div>

        <div class="mt-4 p-3 inline-block bg-white border border-slate-200 rounded-2xl shadow-sm">
            {!! $qrCodeSvg !!}
        </div>

        @if ($setupKey)
        <div class="mt-3 max-w-xl text-xs font-mono text-slate-600 bg-slate-100 p-2.5 rounded-xl border border-slate-200">
            <span class="font-bold text-slate-700">Setup Key:</span> {{ $setupKey }}
        </div>
        @endif

        @if ($showingConfirmation)
        <div class="mt-5 max-w-xs">
            <x-label for="code" value="{{ __('Enter 6-Digit Authenticator Code') }}" />
            <x-input id="code" type="text" name="code" class="block mt-1 w-full text-center tracking-widest text-lg font-mono" inputmode="numeric" autofocus autocomplete="one-time-code"
                placeholder="000000"
                wire:model="code"
                wire:keydown.enter="confirmTwoFactorAuthentication" />
            <x-input-error for="code" class="mt-2" />

            <x-button type="button" class="mt-3 w-full justify-center" wire:click="confirmTwoFactorAuthentication" wire:loading.attr="disabled">
                {{ __('Confirm & Activate') }}
            </x-button>
        </div>
        @endif
        @endif

        {{-- Recovery Codes Section --}}
        @if ($showingRecoveryCodes)
        <div class="mt-4 max-w-xl text-sm text-gray-600">
            <p class="font-semibold text-slate-800">
                {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your authenticator device is lost.') }}
            </p>
        </div>

        <div class="grid gap-1 max-w-xl mt-3 px-4 py-3 font-mono text-xs bg-slate-100 rounded-xl border border-slate-200">
            @foreach ($recoveryCodes as $rCode)
            <div>{{ $rCode }}</div>
            @endforeach
        </div>
        @endif

        {{-- Action Buttons --}}
        <div class="mt-6">
            @if (! $this->enabled && ! $showingQrCode)
            <x-button type="button" wire:click="enableTwoFactorAuthentication" wire:loading.attr="disabled">
                {{ __('Enable') }}
            </x-button>
            @elseif ($this->enabled && ! $showingConfirmation)
            @if ($showingRecoveryCodes)
            <x-secondary-button class="me-3" wire:click="regenerateRecoveryCodes">
                {{ __('Regenerate Recovery Codes') }}
            </x-secondary-button>
            @else
            <x-secondary-button class="me-3" wire:click="showRecoveryCodes">
                {{ __('Show Recovery Codes') }}
            </x-secondary-button>
            @endif

            <x-danger-button wire:click="disableTwoFactorAuthentication" wire:loading.attr="disabled">
                {{ __('Disable') }}
            </x-danger-button>
            @elseif ($showingQrCode && ! $this->enabled)
            <x-secondary-button wire:click="disableTwoFactorAuthentication" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>
            @endif
        </div>
    </x-slot>
</x-action-section>