<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\WebsiteApi;

new class extends Component {
    use WithFileUploads;

    public $state = [];
    public $photo;
    public $user = [];
    public $hasVerifiedEmail = true;
    public $verificationLinkSent = false;

    public function mount()
    {
        $response = WebsiteApi::client()->get('/user/profile');

        if ($response->successful()) {
            $data = $response->json();
            $this->user = $data['user'] ?? $data;
            $this->hasVerifiedEmail = $data['has_verified_email'] ?? true;
            session(['user' => $this->user]);
        } else {
            $this->user = session('user') ?? [];
        }

        $this->state = [
            'name'           => $this->user['name'] ?? '',
            'email'          => $this->user['email'] ?? '',
            'student_number' => $this->user['student_number'] ?? '',
            'phone'          => $this->user['phone'] ?? '',
        ];
    }

    public function updateProfileInformation()
    {
        $this->resetErrorBag();

        $this->validate([
            'state.name'           => 'required|string|max:255',
            'state.email'          => 'required|email|max:255',
            'state.student_number' => 'nullable|string|max:50',
            'state.phone'          => 'nullable|string|max:20',
        ]);

        $response = WebsiteApi::client()->put('/user/profile-information', [
            'name'           => $this->state['name'],
            'email'          => $this->state['email'],
            'student_number' => $this->state['student_number'] ?? null,
            'phone'          => $this->state['phone'] ?? null,
        ]);

        if ($response->successful()) {
            // Upload photo if a new one was selected
            if ($this->photo) {
                $photoRes = WebsiteApi::client()->attach(
                    'photo',
                    file_get_contents($this->photo->getRealPath()),
                    $this->photo->getClientOriginalName()
                )->post('/user/profile-photo');

                if ($photoRes->successful()) {
                    $this->user['profile_photo_url'] = $photoRes->json('profile_photo_url');
                    $this->photo = null;
                }
            }

            // Refresh user session data
            $userRes = WebsiteApi::client()->get('/user/profile');
            if ($userRes->successful()) {
                $this->user = $userRes->json('user') ?? $userRes->json();
                session(['user' => $this->user]);
            }

            session()->flash('status', 'profile-information-updated');
            $this->dispatch('saved');
        } else {
            $data = $response->json();
            $errors = $data['errors'] ?? [];

            foreach ($errors as $field => $messages) {
                $this->addError("state.{$field}", $messages[0]);
            }

            if (empty($errors)) {
                $this->addError('state.email', $data['message'] ?? 'Update failed.');
            }
        }
    }

    public function sendEmailVerification()
    {
        $response = WebsiteApi::client()->post('/email/verification-notification');

        if ($response->successful()) {
            $this->verificationLinkSent = true;
        }
    }

    public function deleteProfilePhoto()
    {
        // Set photo path to null locally
        $this->user['profile_photo_path'] = null;
        $this->user['profile_photo_url'] = null;
        $this->photo = null;
    }
};
?>

<x-form-section submit="updateProfileInformation">
    <x-slot name="title">
        {{ __('Profile Information') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Update your account\'s profile information, identification number, and contact details.') }}
    </x-slot>

    <x-slot name="form">
        {{-- Profile Photo Upload & Preview --}}
        <div x-data="{ photoName: null, photoPreview: null }" class="col-span-6 sm:col-span-4">
            <input type="file" id="photo" class="hidden"
                wire:model.live="photo"
                x-ref="photo"
                x-on:change="
                    photoName = $refs.photo.files[0].name;
                    const reader = new FileReader();
                    reader.onload = (e) => { photoPreview = e.target.result; };
                    reader.readAsDataURL($refs.photo.files[0]);
                " />

            <x-label for="photo" value="{{ __('Photo') }}" />

            {{-- Current Profile Photo --}}
            <div class="mt-2" x-show="! photoPreview">
                @php
                $avatarFallback = 'https://ui-avatars.com/api/?name=' . urlencode($this->state['name'] ?? 'User') . '&color=D4AF37&background=0F172A';
                $currentPhotoUrl = $this->user['profile_photo_url'] ?? $avatarFallback;
                @endphp
                <img src="{{ $currentPhotoUrl }}" alt="{{ $this->user['name'] ?? 'User' }}" class="rounded-full size-20 object-cover shadow border border-slate-200">
            </div>

            {{-- New Profile Photo Preview --}}
            <div class="mt-2" x-show="photoPreview" style="display: none;">
                <span class="block rounded-full size-20 bg-cover bg-no-repeat bg-center shadow border-2 border-[#D4AF37]"
                    x-bind:style="'background-image: url(\'' + photoPreview + '\');'">
                </span>
            </div>

            <x-secondary-button class="mt-3 me-2" type="button" x-on:click.prevent="$refs.photo.click()">
                {{ __('Select A New Photo') }}
            </x-secondary-button>

            @if (!empty($this->user['profile_photo_path']))
            <x-secondary-button type="button" class="mt-3 text-rose-600 hover:text-rose-700" wire:click="deleteProfilePhoto">
                {{ __('Remove Photo') }}
            </x-secondary-button>
            @endif

            <x-input-error for="photo" class="mt-2" />
        </div>

        {{-- Full Name --}}
        <div class="col-span-6 sm:col-span-4">
            <x-label for="name" value="{{ __('Name') }}" />
            <x-input id="name" type="text" class="mt-1 block w-full" wire:model="state.name" required autocomplete="name" />
            <x-input-error for="state.name" class="mt-2" />
        </div>

        {{-- Email Address --}}
        <div class="col-span-6 sm:col-span-4">
            <x-label for="email" value="{{ __('Email') }}" />
            <x-input id="email" type="email" class="mt-1 block w-full" wire:model="state.email" required autocomplete="username" />
            <x-input-error for="state.email" class="mt-2" />

            {{-- Email Verification Notice --}}
            @if (! $this->hasVerifiedEmail)
            <p class="text-xs mt-2 text-amber-600 font-medium">
                {{ __('Your email address is unverified.') }}

                <button type="button" class="underline text-xs text-slate-600 hover:text-slate-900 ml-1 font-bold" wire:click.prevent="sendEmailVerification">
                    {{ __('Click here to re-send the verification email.') }}
                </button>
            </p>

            @if ($this->verificationLinkSent)
            <p class="mt-2 font-bold text-xs text-emerald-600">
                {{ __('A new verification link has been sent to your email address.') }}
            </p>
            @endif
            @endif
        </div>

        {{-- Dynamic Identification Number (Student / Teacher / Admin ID) --}}
        <div class="col-span-6 sm:col-span-4">
            @php
            $role = strtolower($this->user['role'] ?? $this->user['usertype'] ?? 'student');

            $idLabel = match ($role) {
            'student' => __('Student ID'),
            'teacher', 'faculty', 'instructor' => __('Teacher ID'),
            'admin', 'super_admin', 'super-admin' => __('Admin ID Number'),
            default => __('Identification Number'),
            };
            @endphp

            <x-label for="student_number" value="{{ $idLabel }}" />
            <x-input id="student_number" type="text" class="mt-1 block w-full" wire:model="state.student_number" placeholder="e.g. 2024-00123" />
            <x-input-error for="state.student_number" class="mt-2" />
        </div>

        {{-- Contact Number --}}
        <div class="col-span-6 sm:col-span-4">
            <x-label for="phone" value="{{ __('Contact Number') }}" />
            <x-input id="phone" type="text" class="mt-1 block w-full" wire:model="state.phone" placeholder="e.g. +63 912 345 6789" />
            <x-input-error for="state.phone" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-action-message class="me-3" on="saved">
            {{ __('Saved.') }}
        </x-action-message>

        <x-button wire:loading.attr="disabled" wire:target="photo">
            {{ __('Save') }}
        </x-button>
    </x-slot>
</x-form-section>