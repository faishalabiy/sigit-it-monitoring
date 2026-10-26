<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') – SIGIT</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>[x-cloak] { display: none !important; } summary::-webkit-details-marker { display:none; }</style>
</head>

<body class="min-h-screen bg-slate-100 text-slate-950 font-sans" x-data="{ mobileSidebarOpen:false }" @keydown.escape.window="mobileSidebarOpen=false">
    <div class="flex min-h-screen">
        <div x-cloak x-show="mobileSidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/60 md:hidden" @click="mobileSidebarOpen=false"></div>

        <aside id="sigit-sidebar" class="fixed inset-y-0 left-0 z-50 hidden w-64 flex-col bg-slate-950 text-white shadow-xl md:flex md:shadow-none" :class="{ '!flex': mobileSidebarOpen }" @click="if ($event.target.closest('a[href]')) mobileSidebarOpen=false">
            <a href="{{ route('admin.dashboard') }}"
                class="flex items-center gap-3 border-b border-white/10 px-5 py-5 no-underline">

                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 shadow-sm ring-1 ring-white/20">
                    <img
                        src="{{ asset('img/sigit-logo.png') }}"
                        alt="Logo SIGIT"
                        class="h-full w-full object-contain"
                    >
                </span>

                <span class="font-heading text-lg font-bold tracking-tight text-white">
                    S<span class="text-blue-400">IGIT</span>
                </span>
            </a>

            @include('layouts.sidebar-navigation')

            <div class="border-t border-white/10 p-3">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="#" onclick="event.preventDefault(); this.closest('form').submit();"
                        class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/55 transition hover:bg-white/10 hover:text-white">
                        <i class="fa-solid fa-right-from-bracket w-5 text-center"></i>
                        <span>Logout</span>
                    </a>
                </form>
            </div>
        </aside>

        <main class="flex min-h-screen min-w-0 flex-1 flex-col md:ml-64">
            <header
                class="sticky top-0 z-40 flex h-14 items-center justify-between border-b border-slate-200 bg-white px-5 md:px-7">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50 md:hidden" aria-controls="sigit-sidebar" :aria-expanded="mobileSidebarOpen.toString()" aria-label="Buka menu navigasi" @click="mobileSidebarOpen=!mobileSidebarOpen"><i class="fa-solid fa-bars"></i></button>
                <h1 class="font-heading text-base font-bold">
                    @yield('page-title', 'Dashboard')
                </h1>
                </div>

                <div class="flex items-center gap-3 text-sm text-slate-600">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-600">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                    <span class="hidden sm:inline">{{ auth()->user()->name ?? 'Admin' }}</span>
                </div>
            </header>
            <section class="flex-1 p-5 md:p-7">
                @if (session('success'))
                    <div
                        class="mb-4 flex items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        <i class="fa-solid fa-circle-check mt-0.5"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div
                        class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </section>
        </main>

    </div>

    @stack('scripts')
    <script>
        document.querySelectorAll('[data-sidebar-module]').forEach((section) => {
            const key = 'sigit.sidebar.' + section.dataset.sidebarModule;
            const active = section.open;
            try {
                const stored = window.localStorage.getItem(key);
                if (!active && stored !== null) section.open = stored === '1';
            } catch (_) { /* storage optional */ }
            section.addEventListener('toggle', () => {
                try { window.localStorage.setItem(key, section.open ? '1' : '0'); }
                catch (_) { /* storage optional */ }
            });
        });
    </script>
</body>

</html>
