@if($pmSummary)
<section id="pm-summary" class="mb-6 space-y-4" aria-labelledby="pm-summary-title">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Operasional TI</p>
            <h2 id="pm-summary-title" class="mt-1 font-heading text-xl font-bold text-slate-950">Preventive Maintenance</h2>
            <p class="mt-1 text-sm text-slate-500">
                Ringkasan {{ $pmSummary['period'] }}, minggu ke-{{ $pmSummary['week'] }}
                ({{ $pmSummary['week_start']->format('d') }}–{{ $pmSummary['week_end']->locale('id')->translatedFormat('d M Y') }})
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.pm.monthly.index', ['year'=>$pmSummary['year'],'month'=>$pmSummary['month']]) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                <i class="fa-solid fa-table-cells-large"></i> Overview Bulanan
            </a>
            <a href="{{ route('admin.pm.index') }}"
                class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Buka Dashboard PM
            </a>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Aplikasi Diperiksa Minggu Ini</p>
                <i class="fa-solid fa-server text-lg text-blue-500"></i>
            </div>
            <p class="mt-2 font-heading text-3xl font-bold text-slate-950">
                {{ $pmSummary['checked'] }}<span class="text-lg font-medium text-slate-400">/{{ $pmSummary['systems'] }}</span>
            </p>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuenow="{{ $pmSummary['coverage_percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="Cakupan pemeriksaan PM minggu ini">
                <div class="h-full rounded-full bg-blue-600" style="width: {{ $pmSummary['coverage_percent'] }}%"></div>
            </div>
            <p class="mt-2 text-xs text-slate-500">{{ $pmSummary['coverage_percent'] }}% cakupan · {{ $pmSummary['not_checked'] }} belum diperiksa</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Aplikasi Final & Tuntas</p>
                <i class="fa-solid fa-clipboard-check text-lg text-emerald-500"></i>
            </div>
            <p class="mt-2 font-heading text-3xl font-bold text-emerald-600">{{ $pmSummary['handled'] }}</p>
            <p class="mt-2 text-xs text-slate-500">Sistem dengan laporan final serta PASS atau corrective Closed</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Laporan Final Bulan Ini</p>
                <i class="fa-solid fa-file-circle-check text-lg text-blue-500"></i>
            </div>
            <p class="mt-2 font-heading text-3xl font-bold text-slate-950">{{ $pmSummary['finalized_month'] }}</p>
            <a href="{{ route('admin.reports.pm.index', ['review_status'=>'finalized']) }}" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline">Kelola laporan <i class="fa-solid fa-arrow-right"></i></a>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Corrective Terbuka</p>
                <i class="fa-solid fa-triangle-exclamation text-lg {{ $pmSummary['open_correctives']?'text-amber-500':'text-slate-400' }}"></i>
            </div>
            <p class="mt-2 font-heading text-3xl font-bold {{ $pmSummary['open_correctives']?'text-amber-600':'text-slate-950' }}">{{ $pmSummary['open_correctives'] }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ $pmSummary['overdue_correctives'] }} melewati tenggat · Semua sistem PM</p>
        </div>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Status Pemeriksaan Minggu Ini</h3>
                    <p class="mt-1 text-xs text-slate-500">Lima parameter utama · hasil terakhir per sistem</p>
                </div>
                <a href="{{ route('admin.pm.index', ['year'=>$pmSummary['year'],'month'=>$pmSummary['month'],'week'=>$pmSummary['week']]) }}" class="text-xs font-semibold text-blue-600 hover:underline">Detail PM <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs font-bold text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Sistem</th>
                            <th class="px-2 py-3 text-center">Log</th>
                            <th class="px-2 py-3 text-center">Backup</th>
                            <th class="px-2 py-3 text-center">Module</th>
                            <th class="px-2 py-3 text-center">Database</th>
                            <th class="px-2 py-3 text-center">SSL/TLS</th>
                            <th class="px-3 py-3 text-center">Hasil</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($pmSummary['rows'] as $row)
                        @php($label=match($row['display_status']){
                            'final_ok'=>'FINAL · OK',
                            'final_resolved'=>'FINAL · TUNTAS',
                            'final_finding'=>'FINAL · TEMUAN',
                            'final_exception'=>'FINAL · PENGECUALIAN',
                            'ok'=>'OK',
                            'finding'=>'TEMUAN',
                            'failed'=>'GAGAL',
                            'incomplete'=>'BELUM LENGKAP',
                            default=>'BELUM DICEK',
                        })
                        @php($isHandled=in_array($row['display_status'],['final_ok','final_resolved','ok'],true))
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <span class="block font-semibold text-slate-900">{{ $row['system']->name }}</span>
                                <span class="text-xs text-slate-500">{{ $row['checked_at']?->format('d M H:i') ?? 'Belum diperiksa' }}{{ $row['checked_at']?' WIB':'' }}</span>
                            </td>
                            @foreach(['log_error','backup','module','database','ssl'] as $key)
                                @php($status=$row['statuses'][$key])
                                @php($resolved=$row['resolved_flags'][$key])
                                <td class="px-2 py-3 text-center" title="{{ collect($row['details'][$key])->map(fn($entry)=>$entry['name'].': '.$entry['status'].'. '.$entry['message'])->implode(' | ') }}">
                                    @if($resolved)
                                        <span class="inline-flex rounded bg-emerald-50 px-1.5 py-1 text-[10px] font-bold text-emerald-700" aria-label="{{ strtoupper($status) }} Closed">Closed</span>
                                    @elseif($status==='pass')
                                        <i class="fa-solid fa-circle-check text-emerald-600" aria-label="PASS"></i>
                                    @elseif($status==='fail')
                                        <i class="fa-solid fa-circle-xmark text-red-600" aria-label="FAIL"></i>
                                    @elseif($status==='warning')
                                        <i class="fa-solid fa-triangle-exclamation text-amber-600" aria-label="WARNING"></i>
                                    @elseif($status==='unavailable')
                                        <span class="text-xs font-semibold text-slate-500">N/A</span>
                                    @elseif($status==='pending')
                                        <span class="text-xs font-semibold text-amber-600">Manual</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            @endforeach
                            <td class="px-3 py-3 text-center">
                                @if($row['run'])
                                    <a href="{{ route('admin.pm.runs.show',$row['run']) }}"
                                        class="inline-flex whitespace-nowrap rounded-full px-2 py-1 text-[10px] font-bold {{ $isHandled?'bg-emerald-50 text-emerald-700 hover:bg-emerald-100':'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                                        {{ $label }}
                                    </a>
                                @else
                                    <span class="rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-500">{{ $label }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">Belum ada sistem aplikasi aktif untuk dimonitor.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if($pmSummary['infrastructure_count'] > 0)
                <div class="space-y-2 border-t border-slate-100 bg-slate-50/60 px-5 py-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">Monitoring Infrastruktur Server</h4>
                        <span class="text-xs text-slate-500">{{ $pmSummary['infrastructure_checked'] }}/{{ $pmSummary['infrastructure_count'] }} diperiksa minggu ini</span>
                    </div>
                    @foreach($pmSummary['infrastructure'] as $host)
                        @php($hostFinished=in_array($host['status'],['final_ok','final_resolved'],true))
                        @php($hostLabel=match($host['status']){
                            'final_ok'=>'FINAL · OK',
                            'final_resolved'=>'FINAL · TUNTAS',
                            'final_exception'=>'FINAL · PENGECUALIAN',
                            'failed'=>'GAGAL',
                            'review'=>'DALAM REVIEW',
                            default=>'BELUM DICEK'
                        })
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2.5">
                            <div>
                                <p class="text-sm font-semibold text-slate-900"><i class="fa-solid fa-server mr-2 text-slate-400"></i>{{ $host['system']->name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $host['checks'] }} pemeriksaan · {{ $host['closed'] }} tindak lanjut Closed</p>
                            </div>
                            @if($host['run'])
                                <a href="{{ route('admin.pm.runs.show',$host['run']) }}" class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $hostFinished?'bg-emerald-50 text-emerald-700':'bg-amber-50 text-amber-700' }}">{{ $hostLabel }}</a>
                            @else
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-500">{{ $hostLabel }}</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="border-t border-slate-100 px-5 py-3 text-xs leading-5 text-slate-500">
                Cakupan tabel aplikasi berbeda dari monitoring infrastruktur server. Closed menandakan tindak lanjut selesai, bukan mengubah hasil N/A menjadi PASS. Pemeriksaan minggu sebelumnya tidak dihitung untuk minggu ini.
            </div>
        </div>

        <div class="min-w-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                <div>
                    <h3 class="font-semibold text-slate-950">Laporan PM Terbaru</h3>
                    <p class="mt-1 text-xs text-slate-500">Pelaksanaan bulan berjalan</p>
                </div>
                <a href="{{ route('admin.reports.pm.index') }}" class="text-xs font-semibold text-blue-600 hover:underline">Semua laporan <i class="fa-solid fa-arrow-right"></i></a>
            </div>
            <div class="divide-y divide-slate-100">
                @forelse($pmSummary['recent_reports'] as $run)
                    @php($final=$run->review_status==='finalized')
                    <div class="flex items-start justify-between gap-3 px-5 py-3.5">
                        <div class="min-w-0">
                            <a href="{{ route('admin.pm.runs.show',$run) }}" class="block truncate text-sm font-semibold text-slate-900 hover:text-blue-700">{{ $run->schedule_name }}</a>
                            <p class="mt-1 text-xs text-slate-500">Run #{{ $run->id }} · {{ $run->started_at?->timezone('Asia/Jakarta')->format('d M Y H:i') }} WIB</p>
                            @if($run->corrective_total)
                                <p class="mt-1 text-xs {{ $run->corrective_open===0?'text-emerald-700':'text-amber-700' }}">{{ $run->corrective_total-$run->corrective_open }}/{{ $run->corrective_total }} corrective Closed</p>
                            @endif
                        </div>
                        <div class="shrink-0 text-right">
                            <span class="rounded-full px-2 py-1 text-[10px] font-bold {{ $final?'bg-emerald-50 text-emerald-700':'bg-amber-50 text-amber-700' }}">
                                {{ $final?'FINAL':match($run->review_status){'approved'=>'DISETUJUI','under_review'=>'REVIEW',default=>'DRAFT'} }}
                            </span>
                            @if($final)
                                <a href="{{ route('admin.pm.runs.pdf',$run) }}" class="mt-2 block text-xs font-semibold text-blue-600 hover:underline"><i class="fa-solid fa-file-pdf mr-1"></i>PDF</a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm text-slate-500">Belum ada laporan PM dalam bulan berjalan.</p>
                @endforelse
            </div>
            <div class="border-t border-slate-100 px-5 py-3">
                <a href="{{ route('admin.pm.correctives.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-blue-600 hover:underline">
                    <i class="fa-solid fa-list-check"></i>Lihat Semua Tindak Lanjut PM
                </a>
            </div>
        </div>
    </div>
</section>
@endif
