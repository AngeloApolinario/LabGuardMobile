<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'LabGuard') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}?v=2">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="font-sans antialiased bg-slate-50 text-slate-800 min-h-screen overflow-x-hidden">
    <x-banner />

    <div class="min-h-screen relative w-full flex flex-col overflow-x-hidden">

        {{-- Sticky Mobile/Desktop Header --}}
        @if (isset($header))
        <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
            <div class="max-w-7xl mx-auto py-4 sm:py-6 px-4 sm:px-6 lg:px-8">
                {{ $header }}
            </div>
        </header>
        @endif

        {{-- Main Content Container (lg:pl-64 & pt-14 removed) --}}
        <main class="flex-1 w-full max-w-full">
            {{ $slot }}
        </main>

    </div>


    @stack('modals')
    @livewireScripts

</body>

</html>