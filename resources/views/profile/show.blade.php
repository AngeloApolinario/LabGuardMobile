@php
$currentUser = $user ?? session('user') ?? [];
$userRole = is_array($currentUser)
? ($currentUser['role'] ?? 'student')
: ($currentUser->role ?? 'student');

$roleLabel = [
'super-admin' => 'Super Admin',
'super_admin' => 'Super Admin',
'admin' => 'Admin',
'personnel' => 'Personnel',
'student' => 'Student',
][$userRole] ?? ucfirst($userRole);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-black text-xl sm:text-2xl text-slate-800 tracking-tighter uppercase">
                    Personnel <span class="text-[#D4AF37]">File</span>
                </h2>
                <p class="text-[9px] sm:text-[10px] font-bold text-slate-400 uppercase tracking-[0.4em] mt-1">
                    Identity Management System
                </p>
            </div>

            <div class="flex items-center gap-2">
                {{-- Dynamic Clearance Badge --}}
                <div class="px-3 py-1.5 sm:px-4 sm:py-2 bg-white border border-slate-100 rounded-xl shadow-sm">
                    <span class="text-[9px] sm:text-[10px] font-black text-slate-400 uppercase tracking-widest">
                        Clearance:
                        <span class="{{ in_array($userRole, ['admin', 'super-admin', 'super_admin']) ? 'text-[#D4AF37]' : 'text-cyan-500' }}">
                            {{ $roleLabel }}
                        </span>
                    </span>
                </div>

                {{-- Header Quick Logout --}}
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="p-2 sm:px-3 sm:py-2 bg-rose-50 text-rose-600 hover:bg-rose-100 border border-rose-100 rounded-xl text-xs font-bold transition flex items-center gap-1.5">
                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="hidden sm:inline">Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="min-h-screen bg-[#F8FAFC] relative pb-20">
        {{-- Background Glow --}}
        <div class="absolute top-0 left-0 w-full h-full opacity-40 pointer-events-none overflow-hidden">
            <div class="absolute top-[-10%] right-[-5%] w-[40%] h-[40%] bg-[#D4AF37]/10 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-[10%] left-[-5%] w-[30%] h-[30%] bg-indigo-100 rounded-full blur-[100px]"></div>
        </div>

        <div class="max-w-7xl mx-auto py-8 sm:py-12 px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="space-y-12 sm:space-y-16">

                {{-- 1. Profile Information --}}
                <div class="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-2 shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white">
                    <div class="bg-slate-50/50 rounded-[1.8rem] sm:rounded-[2.2rem] p-5 sm:p-8 border border-slate-100/50">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>

                {{-- 2. Update Password --}}
                <div class="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-2 shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white">
                    <div class="bg-slate-50/50 rounded-[1.8rem] sm:rounded-[2.2rem] p-5 sm:p-8 border border-slate-100/50">
                        <livewire:profile.update-password-form />
                    </div>
                </div>

                {{-- 3. Two Factor Authentication --}}
                <div class="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-2 shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-[#D4AF37]/5 rounded-bl-full pointer-events-none"></div>
                    <div class="bg-slate-50/50 rounded-[1.8rem] sm:rounded-[2.2rem] p-5 sm:p-8 border border-slate-100/50 relative z-10">
                        <livewire:profile.two-factor-authentication-form />
                    </div>
                </div>

                {{-- 4. Browser Sessions --}}
                <div class="bg-white rounded-[2rem] sm:rounded-[2.5rem] p-2 shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-white">
                    <div class="bg-slate-50/50 rounded-[1.8rem] sm:rounded-[2.2rem] p-5 sm:p-8 border border-slate-100/50">
                        <livewire:profile.logout-other-browser-sessions-form />
                    </div>
                </div>

                {{-- 5. Delete Account --}}
                <div class="mt-16 sm:mt-20">
                    <div class="flex items-center space-x-4 mb-6 px-4 sm:px-8">
                        <div class="h-px flex-grow bg-slate-200"></div>
                        <span class="text-[10px] font-black text-rose-400 uppercase tracking-[0.4em]">Decommission Account</span>
                        <div class="h-px flex-grow bg-slate-200"></div>
                    </div>
                    <div class="bg-rose-50/30 rounded-[2rem] sm:rounded-[2.5rem] p-2 border border-rose-100">
                        <div class="bg-white rounded-[1.8rem] sm:rounded-[2.2rem] p-5 sm:p-8 shadow-sm">
                            <livewire:profile.delete-user-form />
                        </div>
                    </div>
                </div>
            </div>

            {{-- Mobile Full-Width Logout Card --}}
            <div class="mt-14 max-w-xl mx-auto">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full py-4 bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 rounded-2xl shadow-sm font-bold text-xs uppercase tracking-[0.2em] flex items-center justify-center gap-2 transition active:scale-[0.98]">
                        <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sign Out of LabGuard
                    </button>
                </form>
            </div>

            {{-- Footer Branding --}}
            <div class="mt-16 sm:mt-20 text-center">
                <div class="inline-block px-5 py-2 bg-slate-100 rounded-full">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-[0.4em]">
                        LabGuard &bull; Computer Laboratory Management System
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>