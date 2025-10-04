<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('theme') === 'dark' }" 
      x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))"
      x-bind:class="{ 'dark': darkMode }">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        {{-- ✅ Auto-detect system preference on first load --}}
        <script>
        if (!localStorage.getItem('theme')) {
            localStorage.setItem('theme', window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        }
    </script>
    </head>
    <body class="font-sans antialiased">

        {{-- ✅ Floating Dark/Light Mode 🌗 Toggle Button --}}
    <button 
        @click="darkMode = !darkMode"
        class="fixed bottom-6 right-6 ml-3 z-50 p-3 rounded-full shadow-lg bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 hover:scale-110 transition-transform duration-200"
        title="Toggle Dark/Light Mode"
    >
        {{-- 🌙 Moon Icon (light mode → switch to dark) --}}
        <svg x-show="!darkMode" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0112 21.75a9.75 9.75 0 010-19.5c.341 0 .677.02 1.008.058a9.75 9.75 0 008.744 12.694z" />
        </svg>

        {{-- ☀️ Sun Icon (dark mode → switch to light) --}}
        <svg x-show="darkMode" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m0 13.5V21m9-9h-2.25M5.25 12H3m15.364 6.364l-1.591-1.591M7.227 7.227L5.636 5.636m12.728 0l-1.591 1.591M7.227 16.773l-1.591 1.591M12 6.75a5.25 5.25 0 110 10.5 5.25 5.25 0 010-10.5z" />
        </svg>
    </button>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
