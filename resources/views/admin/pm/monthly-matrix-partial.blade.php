<div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5">
        <div>
            <h3 class="font-heading text-lg font-bold text-slate-900">
                <i class="fa-solid fa-table-cells-large mr-2 text-blue-600"></i>Overview PM Bulanan
            </h3>
            <p class="mt-1 text-sm text-slate-500">
                {{ $monthlyOverview['start']->locale('id')->translatedFormat('F Y') }} · Hasil terakhir setiap sistem per minggu
            </p>
        </div>
        @if(request()->routeIs('admin.pm.monthly.index'))
          <a href="{{ route('admin.pm.index', ['year'=>$selectedYear, 'month'=>$selectedMonth]) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-slate-50">
             <i class="fa-solid fa-arrow-left"></i> Dashboard PM
          </a>
        @else
          <a href="{{ route('admin.pm.monthly.index', ['year'=>$selectedYear, 'month'=>$selectedMonth]) }}"
             class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-100">
             Tabel Detail Bulanan <i class="fa-solid fa-arrow-right"></i>
          </a>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-3 px-5 py-4 lg:grid-cols-4">
        @foreach([
          ['Sistem Dipantau', $monthlyOverview['stats']['systems'], 'fa-server'],
          ['Tuntas s.d. Minggu Ini', $monthlyOverview['stats']['completed'], 'fa-check-double'],
          ['Perlu Tindak Lanjut', $monthlyOverview['stats']['needs_attention'], 'fa-triangle-exclamation'],
          ['Dalam Proses', $monthlyOverview['stats']['in_progress'], 'fa-clock']
        ] as $card)
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p class="text-xs text-slate-500"><i class="fa-solid {{ $card[2] }} mr-1"></i>{{ $card[0] }}</p>
            <p class="mt-1 text-xl font-bold text-slate-900">{{ $card[1] }}</p>
        </div>
        @endforeach
    </div>

    <div class="overflow-x-auto px-5 pb-5">
        <table class="w-full min-w-[820px] border-collapse text-sm">
            <thead class="bg-slate-50 text-slate-600">
                <tr>
                    <th class="border border-slate-200 px-3 py-3 text-left">Sistem</th>
                    @foreach($monthlyOverview['weeks'] as $week)
                    <th class="border border-slate-200 px-3 py-3 text-center">
                        <a class="hover:text-blue-600" href="{{ route('admin.pm.index', ['year'=>$selectedYear,'month'=>$selectedMonth,'week'=>$week['number']]) }}">
                            Minggu {{ $week['number'] }}
                        </a>
                        <span class="mt-0.5 block text-[11px] font-normal">{{ $week['start']->format('d') }}–{{ $week['end']->format('d M') }}</span>
                    </th>
                    @endforeach
                    <th class="border border-slate-200 px-3 py-3 text-center">Tuntas / Jatuh Tempo</th>
                    <th class="border border-slate-200 px-3 py-3 text-center">Ringkasan Bulan</th>
                </tr>
            </thead>
            <tbody>
            @forelse($monthlyOverview['rows'] as $row)
                <tr class="hover:bg-slate-50">
                    <td class="border border-slate-200 px-3 py-3 font-semibold text-slate-900">
                        {{ $row['system']->name }}
                        <span class="block text-xs font-normal text-slate-500">{{ $row['system']->code }}</span>
                    </td>
                    @foreach($monthlyOverview['weeks'] as $week)
                        @php($cell=$row['weeks'][$week['number']] ?? ['status'=>'not_checked','run'=>null,'date'=>null])
                        @php($status=$cell['status'])
                        <td class="border border-slate-200 px-2 py-3 text-center">
                        @if($status==='future')
                            <span class="text-xs text-slate-400">Belum jatuh tempo</span>
                        @elseif($status==='not_checked')
                            <span class="text-xs font-semibold text-slate-500">Belum dicek</span>
                        @else
                            @php($isDone=in_array($status,['final_ok','final_resolved','ok'],true))
                            @php($caption=match($status) {
                                'final_ok'=>'Final · OK',
                                'final_resolved'=>'Final · Tuntas',
                                'final_exception'=>'Final · Pengecualian',
                                'final_finding'=>'Final · Temuan',
                                'ok'=>'OK',
                                'finding'=>'Temuan',
                                'failed'=>'Gagal',
                                default=>'Belum lengkap'
                            })
                            <a href="{{ route('admin.pm.runs.show',$cell['run']) }}"
                               class="inline-block rounded-full px-2 py-1 text-xs font-semibold {{ $isDone?'bg-emerald-50 text-emerald-700 hover:bg-emerald-100':'bg-amber-50 text-amber-700 hover:bg-amber-100' }}">
                               {{ $caption }}
                            </a>
                            <span class="mt-1 block text-[11px] text-slate-500">{{ $cell['date']?->format('d M') }}</span>
                        @endif
                        </td>
                    @endforeach
                    <td class="border border-slate-200 px-3 py-3 text-center font-semibold text-slate-800">
                        {{ $row['completed'] }} / {{ $row['due'] }}
                    </td>
                    <td class="border border-slate-200 px-3 py-3 text-center">
                        @php($summary=match($row['monthly_status']) {
                            'completed'=>['Tuntas','bg-emerald-50 text-emerald-700'],
                            'needs_attention'=>['Perlu Tindak Lanjut','bg-amber-50 text-amber-700'],
                            'scheduled'=>['Terjadwal','bg-slate-100 text-slate-600'],
                            default=>['Berjalan','bg-blue-50 text-blue-700']
                        })
                        <span class="inline-block rounded-full px-2 py-1 text-xs font-bold {{ $summary[1] }}">{{ $summary[0] }}</span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($monthlyOverview['weeks'])+3 }}" class="border border-slate-200 p-8 text-center text-slate-500">Belum ada sistem aplikasi aktif untuk periode ini.</td></tr>
            @endforelse
            </tbody>
        </table>
        <p class="mt-3 text-xs leading-5 text-slate-500">
            Rekap menggunakan hasil pemeriksaan terakhir pada tiap minggu. Tuntas berarti laporan final serta semua item lulus atau corrective-nya telah Closed. Minggu yang belum jatuh tempo tidak dihitung sebagai keterlambatan. Klik status untuk membuka laporan.
        </p>
    </div>
</div>
