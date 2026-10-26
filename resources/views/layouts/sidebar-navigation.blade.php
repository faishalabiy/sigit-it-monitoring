<nav class="flex-1 space-y-2 overflow-y-auto px-3 py-4" aria-label="Navigasi modul SIGIT">
    <a href="{{ route('admin.dashboard') }}"
       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard')?'bg-blue-600 text-white':'text-slate-300 hover:bg-white/10 hover:text-white' }}">
        <i class="fa-solid fa-chart-pie w-5 text-center"></i><span>Dashboard Utama</span>
    </a>

    @if(auth()->user()->hasPermission('assets.view') || auth()->user()->hasPermission('borrowings.view'))
    <details data-sidebar-module="assets" class="group rounded-xl border border-white/10 bg-white/[.03]" @if(request()->routeIs('admin.assets.*','admin.borrowings.*')) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-white/85 hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-400">
            <i class="fa-solid fa-boxes-stacked w-5 text-center text-blue-300"></i>
            <span class="flex-1">Aset & Peminjaman</span>
            <i class="fa-solid fa-chevron-down text-xs text-white/50 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="space-y-1 border-t border-white/10 px-2 pb-2 pt-2">
            @if(auth()->user()->hasPermission('assets.view'))
            <a href="{{ route('admin.assets.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs('admin.assets.*')?'bg-blue-600 text-white':'text-white/65 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-laptop w-5 text-center"></i>Aset IT
            </a>
            @endif
            @if(auth()->user()->hasPermission('borrowings.view'))
            <a href="{{ route('admin.borrowings.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs('admin.borrowings.*')?'bg-blue-600 text-white':'text-white/65 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-arrows-rotate w-5 text-center"></i><span class="flex-1">Peminjaman</span>
                @php($overdue=\App\Models\Borrowing::where('status','overdue')->count())
                @if($overdue>0)<span class="rounded-full bg-red-500/20 px-2 py-0.5 text-xs font-semibold text-red-300">{{ $overdue }}</span>@endif
            </a>
            @endif
        </div>
    </details>
    @endif

    @if(auth()->user()->hasPermission('pm.view'))
    <details data-sidebar-module="pm" class="group rounded-xl border border-white/10 bg-white/[.03]" @if(request()->routeIs('admin.pm.*')) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-white/85 hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-400">
            <i class="fa-solid fa-screwdriver-wrench w-5 text-center text-blue-300"></i>
            <span class="flex-1">Preventive Maintenance</span>
            <i class="fa-solid fa-chevron-down text-xs text-white/50 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="space-y-1 border-t border-white/10 px-2 pb-2 pt-2">
            @foreach([
                ['admin.pm.index', 'Dashboard PM', 'fa-chart-line', 'admin.pm.index'],
                ['admin.pm.monthly.index', 'Overview Bulanan', 'fa-table-cells-large', 'admin.pm.monthly.*'],
                ['admin.pm.systems.index', 'Master Sistem', 'fa-server', 'admin.pm.systems.*'],
                ['admin.pm.parameters.index', 'Template Parameter', 'fa-list-check', 'admin.pm.parameters.*'],
                ['admin.pm.schedules.index', 'Jadwal PM', 'fa-calendar-check', 'admin.pm.schedules.*'],
                ['admin.pm.runs.index', 'Riwayat Monitoring', 'fa-clipboard-list', 'admin.pm.runs.*'],
                ['admin.pm.backups.index', 'Monitoring Backup', 'fa-box-archive', 'admin.pm.backups.*'],
                ['admin.pm.correctives.index', 'Tindak Lanjut PM', 'fa-list-check', 'admin.pm.correctives.*'],
                ['admin.pm.notifications.index', 'Notifikasi PM', 'fa-bell', 'admin.pm.notifications.*'],
            ] as $menu)
            <a href="{{ route($menu[0]) }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs($menu[3])?'bg-blue-600 text-white':'text-white/65 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid {{ $menu[2] }} w-5 text-center"></i><span class="flex-1">{{ $menu[1] }}</span>
                @if($menu[0]==='admin.pm.notifications.index')
                    @php($pmUnread=\App\Models\PmNotification::where('user_id',auth()->id())->whereNull('read_at')->count())
                    @if($pmUnread)<span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $pmUnread }}</span>@endif
                @endif
            </a>
            @endforeach
        </div>
    </details>
    @endif

    @if(auth()->user()->hasPermission('reports.view') || auth()->user()->hasPermission('pm.view'))
    <details data-sidebar-module="reports" class="group rounded-xl border border-white/10 bg-white/[.03]" @if(request()->routeIs('admin.reports.*')) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-white/85 hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-400">
            <i class="fa-solid fa-file-lines w-5 text-center text-blue-300"></i><span class="flex-1">Laporan</span>
            <i class="fa-solid fa-chevron-down text-xs text-white/50 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="space-y-1 border-t border-white/10 px-2 pb-2 pt-2">
            @if(auth()->user()->hasPermission('reports.view'))
            <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs('admin.reports.index','admin.reports.export')?'bg-blue-600 text-white':'text-white/65 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-file-export w-5 text-center"></i>Laporan Aset & Peminjaman
            </a>
            @endif
            @if(auth()->user()->hasPermission('pm.view'))
            <a href="{{ route('admin.reports.pm.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs('admin.reports.pm.*')?'bg-blue-600 text-white':'text-white/65 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-file-circle-check w-5 text-center"></i>Laporan Preventive Maintenance
            </a>
            @endif
        </div>
    </details>
    @endif

    @if(auth()->user()->hasPermission('users.manage') || auth()->user()->hasPermission('roles.manage'))
    <details data-sidebar-module="settings" class="group rounded-xl border border-white/10 bg-white/[.03]" @if(request()->routeIs('admin.users.*','admin.roles.*')) open @endif>
        <summary class="flex cursor-pointer list-none items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-white/85 hover:bg-white/10 focus-visible:outline focus-visible:outline-2 focus-visible:outline-blue-400">
            <i class="fa-solid fa-gear w-5 text-center text-blue-300"></i><span class="flex-1">Administrasi</span>
            <i class="fa-solid fa-chevron-down text-xs text-white/50 transition-transform group-open:rotate-180"></i>
        </summary>
        <div class="space-y-1 border-t border-white/10 px-2 pb-2 pt-2">
            @if(auth()->user()->hasPermission('users.manage'))
            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs('admin.users.*')?'bg-blue-600 text-white':'text-white/65 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-users w-5 text-center"></i>Pengguna
            </a>
            @endif
            @if(auth()->user()->hasPermission('roles.manage'))
            <a href="{{ route('admin.roles.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm {{ request()->routeIs('admin.roles.*')?'bg-blue-600 text-white':'text-white/65 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-user-shield w-5 text-center"></i>Role & Hak Akses
            </a>
            @endif
        </div>
    </details>
    @endif

    <div class="px-2 pt-3 text-[10px] font-semibold uppercase tracking-wider text-white/40">Tautan</div>
    <a href="{{ route('public.landing') }}" target="_blank" rel="noopener noreferrer"
       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-white/60 hover:bg-white/10 hover:text-white">
        <i class="fa-solid fa-arrow-up-right-from-square w-5 text-center"></i>Halaman Publik
    </a>
</nav>
