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
</head>

<body class="min-h-screen bg-slate-100 text-slate-950 font-sans">
    <div class="flex min-h-screen">

        <aside class="fixed inset-y-0 left-0 z-50 hidden w-60 flex-col bg-slate-950 text-white md:flex">
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

            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 pt-3 text-[10px] font-bold uppercase tracking-widest text-white/30">Utama</p>

                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
               {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                    <span>Dashboard</span>
                </a>

                <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-white/30">Manajemen</p>

                <a href="{{ route('admin.assets.index') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
               {{ request()->routeIs('admin.assets.*') ? 'bg-blue-600 text-white' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <i class="fa-solid fa-laptop w-5 text-center"></i>
                    <span>Aset IT</span>
                </a>

                <a href="{{ route('admin.borrowings.index') }}"
                    class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
               {{ request()->routeIs('admin.borrowings.*') ? 'bg-blue-600 text-white' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <i class="fa-solid fa-arrows-rotate w-5 text-center"></i>
                    <span>Peminjaman</span>

                    @php $overdue = \App\Models\Borrowing::where('status','overdue')->count(); @endphp
                    @if ($overdue > 0)
                        <span class="ml-auto rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-semibold text-red-300">
                            {{ $overdue }}
                        </span>
                    @endif
                </a>

                <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-white/30">Laporan</p>

                <a href="{{ route('admin.reports.index') }}"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
               {{ request()->routeIs('admin.reports.*') ? 'bg-blue-600 text-white' : 'text-white/65 hover:bg-white/10 hover:text-white' }}">
                    <i class="fa-solid fa-file-lines w-5 text-center"></i>
                    <span>Laporan</span>
                </a>

                <p class="px-3 pb-2 pt-5 text-[10px] font-bold uppercase tracking-widest text-white/30">Publik</p>

                <a href="{{ route('public.landing') }}" target="_blank"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-white/65 transition hover:bg-white/10 hover:text-white">
                    <i class="fa-solid fa-arrow-up-right-from-square w-5 text-center"></i>
                    <span>Halaman Publik</span>
                </a>
            </nav>

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

        <main class="flex min-h-screen flex-1 flex-col md:ml-60">
            <header
                class="sticky top-0 z-40 flex h-14 items-center justify-between border-b border-slate-200 bg-white px-5 md:px-7">
                <h1 class="font-heading text-base font-bold">
                    @yield('page-title', 'Dashboard')
                </h1>

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
</body>

</html>
