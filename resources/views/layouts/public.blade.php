<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIGIT')</title>

    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=Geist:wght@300;400;500;600;700&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        display: ['Syne', 'sans-serif'],
                        body:    ['Geist', 'sans-serif'],
                        mono:    ['Geist Mono', 'monospace'],
                    },
                    colors: {
                        ink:              '#0a0d14',
                        'ink-soft':       '#4a5068',
                        'ink-muted':      '#8b92a8',
                        surface:          '#ffffff',
                        's2':             '#f5f7fc',
                        's3':             '#eceef6',
                        border:           '#e0e4f0',
                        accent:           '#2563eb',
                        'accent-2':       '#1d4ed8',
                        'accent-light':   '#eff4ff',
                        emerald:          '#059669',
                        'emerald-light':  '#ecfdf5',
                        danger:           '#dc2626',
                        'danger-light':   '#fef2f2',
                        amber:            '#d97706',
                    },
                    boxShadow: {
                        'card':  '0 2px 8px rgba(10,13,20,.06), 0 1px 3px rgba(10,13,20,.04)',
                        'lift':  '0 8px 32px rgba(10,13,20,.1),  0 2px 8px rgba(10,13,20,.06)',
                        'glow':  '0 0 0 3px rgba(37,99,235,.12)',
                        'gbtn':  '0 4px 16px rgba(37,99,235,.35)',
                        'gbtng': '0 4px 16px rgba(5,150,105,.35)',
                    },
                    keyframes: {
                        fadeup: {
                            '0%':   { opacity: '0', transform: 'translateY(16px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                        pulse2: {
                            '0%,100%': { opacity: '1' },
                            '50%':     { opacity: '.45' },
                        },
                        shimmer: {
                            '0%':   { backgroundPosition: '-200% 0' },
                            '100%': { backgroundPosition: '200% 0' },
                        },
                    },
                    animation: {
                        'fadeup':   'fadeup .5s ease both',
                        'fadeup-2': 'fadeup .5s .1s ease both',
                        'fadeup-3': 'fadeup .5s .2s ease both',
                        'fadeup-4': 'fadeup .5s .3s ease both',
                        'pulse2':   'pulse2 2s ease-in-out infinite',
                    },
                },
            },
        }
    </script>

    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Geist', sans-serif; }

        /* Dot grid background – dapat dipakai di halaman manapun */
        .hero-grid {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60'%3E%3Cpath d='M60 0v60M0 60h60' stroke='%23dde2f0' stroke-width='0.6'/%3E%3C/svg%3E");
        }

        /* Noise grain overlay */
        .hero-grain::after {
            content: ''; position: absolute; inset: 0; pointer-events: none;
            background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='.03'/%3E%3C/svg%3E");
            opacity: .4;
        }

        /* Animated scan line */
        .scan-line {
            background: linear-gradient(90deg, transparent, rgba(37,99,235,.12) 40%, rgba(37,99,235,.22) 50%, rgba(37,99,235,.12) 60%, transparent);
            background-size: 200% 100%;
            animation: shimmer 3.5s linear infinite;
        }

        /* Service card hover */
        .service-card { transition: transform .25s ease, box-shadow .25s ease; }
        .service-card:hover { transform: translateY(-4px); }

        /* Dashed icon ring */
        .icon-ring {
            position: absolute; inset: -5px; border-radius: 20px;
            border: 1.5px dashed transparent; transition: border-color .25s;
        }
        .borrow-card:hover .icon-ring { border-color: rgba(37,99,235,.3); }
        .return-card:hover  .icon-ring { border-color: rgba(5,150,105,.3); }

        /* Arrow slide on card hover */
        .arrow-icon { transition: transform .2s ease; }
        .service-card:hover .arrow-icon { transform: translateX(5px); }

        /* Status result box */
        .status-result { display: none; }
        .status-result.show { display: block; animation: fadeup .3s ease; }

        /* Input focus ring */
        .form-control:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37,99,235,.12);
        }

        /* Stat number hover */
        .stat-num { transition: color .2s; }
        .stat-item:hover .stat-num { color: #2563eb; }

        /* Badge dot pulse */
        .dot-pulse { animation: pulse2 2s ease-in-out infinite; }

        /* Mini asset card */
        .mini-card { backdrop-filter: blur(6px); transition: transform .2s ease; }
        .mini-card:hover { transform: scale(1.03); }
    </style>

    {{-- Slot untuk tambahan <head> per halaman (opsional) --}}
    @stack('head')
</head>
<body class="bg-s2 text-ink min-h-screen antialiased">

{{-- ─────────────────── HEADER ─────────────────── --}}
<header class="bg-surface/90 backdrop-blur-md border-b border-border sticky top-0 z-50">
    <div class="max-w-6xl mx-auto px-6 h-16 flex items-center justify-between">

        {{-- Logo --}}
        <a href="{{ route('public.landing') }}" class="flex items-center gap-2.5 no-underline group">
            <img
                src="{{ asset('img/sigit-logo.png') }}"
                alt="Logo SIGIT"
                class="h-9 w-9 object-contain transition-transform duration-200 group-hover:scale-105"
            >

            <span class="font-display font-bold text-lg text-ink tracking-tight">
                S<span class="text-accent">IGIT</span>
            </span>
        </a>

        {{-- Nav default – halaman anak bisa override via @section('nav') --}}
        <nav class="flex items-center gap-3">
            @section('nav')
                <a href="{{ route('public.landing') }}#cek-status"
                   class="text-sm text-ink-soft hover:text-ink px-3 py-1.5 rounded-lg hover:bg-s2 transition-all duration-150">
                    <i class="fas fa-magnifying-glass text-xs mr-1.5"></i>Cek Status
                </a>
                <a href="{{ route('admin.dashboard') }}"
                   class="text-sm font-medium text-accent border border-accent px-4 py-1.5 rounded-lg hover:bg-accent hover:text-white transition-all duration-150">
                    <i class="fas fa-lock text-xs mr-1.5"></i>Admin
                </a>
            @show
        </nav>
    </div>
</header>

{{-- ─────────────────── FLASH ALERTS ─────────────────── --}}
@if (session('success') || session('error'))
    <div class="max-w-6xl mx-auto px-6 pt-5 space-y-3">
        @if (session('success'))
            <div class="flex items-start gap-3 bg-emerald-light border border-emerald/25 text-emerald rounded-xl px-4 py-3 text-sm animate-fadeup">
                <i class="fas fa-circle-check mt-0.5 shrink-0"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="flex items-start gap-3 bg-danger-light border border-danger/20 text-danger rounded-xl px-4 py-3 text-sm animate-fadeup">
                <i class="fas fa-circle-exclamation mt-0.5 shrink-0"></i>
                {{ session('error') }}
            </div>
        @endif
    </div>
@endif

{{-- ─────────────────── KONTEN HALAMAN ─────────────────── --}}
@yield('content')

{{-- ─────────────────── FOOTER ─────────────────── --}}
<footer class="bg-surface border-t border-border py-6 text-center text-xs text-ink-muted">
    <img
        src="{{ asset('img/sigit-logo.png') }}"
        alt="Logo SIGIT"
        class="inline-block h-4 w-4 object-contain align-[-3px] mr-1.5"
    >
    SIGIT &copy; {{ date('Y') }} &mdash; Powered by Information Technology PT. Rekaindo Global Jasa
</footer>

{{-- Slot untuk script per halaman --}}
@stack('scripts')

</body>
</html>
