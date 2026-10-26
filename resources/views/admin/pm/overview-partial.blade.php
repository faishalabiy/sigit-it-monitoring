<div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
  <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div>
      <h3 class="text-lg font-bold text-slate-900"><i class="fa-solid fa-clipboard-check mr-2 text-blue-600"></i>Overview Monitoring Preventive Maintenance</h3>
      <p class="mt-1 text-sm font-semibold text-slate-700">Monitoring Periode {{ \Carbon\Carbon::create($selectedYear,$selectedMonth,1)->locale('id')->translatedFormat('F Y') }} — Minggu ke-{{ $selectedWeek }}</p>
      <p class="text-xs text-slate-500">{{ $overview['start']->locale('id')->translatedFormat('d M Y') }} s.d. {{ $overview['end']->locale('id')->translatedFormat('d M Y') }} (WIB)</p>
    </div>
    <form method="GET" action="{{ route('admin.pm.index') }}" class="flex flex-wrap items-end gap-2">
      <label class="text-xs font-semibold text-slate-600">Tahun<select name="year" class="mt-1 block rounded-lg border-slate-300 text-sm">@for($y=now()->year+1;$y>=2024;$y--)<option value="{{ $y }}" @selected($y===$selectedYear)>{{ $y }}</option>@endfor</select></label>
      <label class="text-xs font-semibold text-slate-600">Bulan<select name="month" class="mt-1 block rounded-lg border-slate-300 text-sm">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($m===$selectedMonth)>{{ \Carbon\Carbon::create(2026,$m,1)->locale('id')->translatedFormat('F') }}</option>@endfor</select></label>
      <label class="text-xs font-semibold text-slate-600">Minggu<select name="week" class="mt-1 block rounded-lg border-slate-300 text-sm">@for($w=1;$w<=$weekCount;$w++)<option value="{{ $w }}" @selected($w===$selectedWeek)>Minggu {{ $w }}</option>@endfor</select></label>
      <button class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white">Tampilkan</button>
    </form>
  </div>
  <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4 2xl:grid-cols-8">
    @foreach([['Total Sistem',$overview['stats']['total'],'fa-server'],['Sudah Dicek',$overview['stats']['checked'],'fa-circle-check'],['OK Teknis',$overview['stats']['ok'],'fa-check'],['Laporan Final',$overview['stats']['finalized'],'fa-file-circle-check'],['Tuntas via Corrective',$overview['stats']['resolved'],'fa-check-double'],['Temuan Terbuka',$overview['stats']['finding'],'fa-triangle-exclamation'],['Final dgn. Pengecualian',$overview['stats']['exceptions'],'fa-clipboard-question'],['Belum Selesai',$overview['stats']['incomplete'],'fa-clock']] as $card)
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-3"><div class="text-xs text-slate-600"><i class="fa-solid {{ $card[2] }} mr-1"></i>{{ $card[0] }}</div><div class="mt-1 text-2xl font-bold text-slate-900">{{ $card[1] }}</div></div>
    @endforeach
  </div>
  <div class="overflow-x-auto rounded-lg border border-slate-200">
    <table class="w-full min-w-[1040px] border-collapse text-sm">
      <thead class="bg-slate-100 text-slate-700"><tr><th class="border-b border-r border-slate-200 p-3">No</th><th class="border-b border-r border-slate-200 p-3 text-left">Sistem</th>
        @foreach($overview['columns'] as $column)<th class="border-b border-r border-slate-200 p-3 text-center">{{ $column['title'] }}</th>@endforeach
        <th class="border-b border-r border-slate-200 p-3">PIC</th><th class="border-b border-r border-slate-200 p-3">Tgl Cek</th><th class="border-b border-slate-200 p-3">Result</th></tr></thead>
      <tbody>
      @forelse($overview['rows'] as $row)
      <tr class="border-b border-slate-100 hover:bg-slate-50">
        <td class="p-3 text-center text-slate-500">{{ $loop->iteration }}</td>
        <td class="p-3 font-semibold text-slate-800">{{ $row['system']->name }}</td>
        @foreach($overview['columns'] as $key=>$column)
        @php($status=$row['statuses'][$key])
        @php($isResolved=$row['resolved_flags'][$key])
        <td class="p-3 text-center" title="{{ collect($row['details'][$key])->map(fn($item)=>$item['name'].': '.$item['status'].' - '.$item['message'])->implode(' | ') }}">
        @if($isResolved)<span class="text-xs font-semibold text-emerald-700">{{ strtoupper($status) }} · Closed</span>
        @elseif($status==='pass')<span class="font-bold text-emerald-600" aria-label="OK">✓</span>
        @elseif($status==='fail')<span class="font-bold text-red-600" aria-label="Gagal">✕</span>
        @elseif($status==='warning')<span class="font-bold text-amber-600" aria-label="Peringatan">!</span>
        @elseif($status==='pending')<span class="text-xs font-semibold text-amber-600">Manual</span>
        @elseif($status==='unavailable')<span class="text-xs font-semibold text-slate-500">N/A</span>
        @else<span class="text-slate-400">—</span>@endif
        </td>
        @endforeach
        <td class="p-3 text-center text-slate-700">{{ $row['pic'] }}</td>
        <td class="p-3 text-center text-slate-600">{{ $row['checked_at']?->format('d M Y') ?? '—' }}</td>
        <td class="p-3 text-center">
          @php($labels=[
            'ok'=>['OK','bg-emerald-50 text-emerald-700'],
            'finding'=>['TEMUAN','bg-amber-50 text-amber-700'],
            'failed'=>['GAGAL','bg-red-50 text-red-700'],
            'incomplete'=>['BELUM LENGKAP','bg-amber-50 text-amber-700'],
            'not_checked'=>['BELUM DICEK','bg-slate-100 text-slate-600'],
            'final_ok'=>['FINAL · OK','bg-emerald-50 text-emerald-700'],
            'final_resolved'=>['FINAL · TUNTAS','bg-emerald-50 text-emerald-700'],
            'final_exception'=>['FINAL · PENGECUALIAN','bg-amber-50 text-amber-700'],
            'final_finding'=>['FINAL · TEMUAN','bg-amber-50 text-amber-700']
          ])
          <span class="inline-block rounded-full px-2 py-1 text-xs font-bold {{ $labels[$row['display_status']][1] }}">{{ $labels[$row['display_status']][0] }}</span>
          @if($row['display_status']==='final_exception')
            <span class="mt-1 block text-xs text-amber-700">Ada item N/A / tidak dinilai</span>
          @endif
          @if($row['run'])<a href="{{ route('admin.pm.runs.show',$row['run']) }}" class="mt-1 block text-xs font-semibold text-blue-600">Detail <i class="fa-solid fa-arrow-up-right-from-square"></i></a>@endif
        </td>
      </tr>
      @empty<tr><td colspan="10" class="p-8 text-center text-slate-500">Belum ada sistem aplikasi aktif.</td></tr>@endforelse
      </tbody>
    </table>
  </div>
  <p class="mt-3 text-xs leading-5 text-slate-500">✓ Lulus · ✕ Gagal · ! Peringatan · Manual Perlu pemeriksaan petugas · N/A Pemeriksaan tidak tersedia · — Belum ada hasil. FINAL berarti laporan sudah disahkan. FINAL · TUNTAS berarti semua hasil non-PASS telah memiliki corrective Closed, meskipun status teknis historis tetap N/A/FAIL/WARNING. FINAL · PENGECUALIAN berarti masih ada hasil tanpa corrective Closed atau tanpa pemeriksaan. Rekap menggunakan hasil terakhir setiap sistem dalam minggu terpilih dan tidak mengubah laporan final historis.</p>
</div>
