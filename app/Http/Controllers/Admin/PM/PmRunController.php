<?php

namespace App\Http\Controllers\Admin\PM;

use App\Http\Controllers\Controller;
use App\Models\PmRun;
use App\Models\PmRunCheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use App\Models\PmSchedule;
use App\Services\PmExecutionService;
use App\Services\PmCorrectiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PmRunController extends Controller
{
    public function index(): View
    {
        $runs=PmRun::with('initiator')
            ->withCount([
                'correctives as corrective_total',
                'correctives as corrective_open'=>fn($query)=>$query->where('status','!=','resolved'),
            ])
            ->latest('id')->paginate(20);
        return view('admin.pm.runs.index',compact('runs'));
    }

    public function show(PmRun $run): View
    {
        $run->load(['initiator','correctives'=>fn ($q)=>$q->with(['assignee','check'])->orderByDesc('id'),
            'approvalEvents'=>fn($q)=>$q->with('actor')->orderBy('id'),
            'reviewRequester','approver','finalizer', 'checks'=>fn ($q)=>$q->with('correctiveAction')->orderBy('system_name')->orderBy('id')]);
        return view('admin.pm.runs.show',compact('run'));
    }

    public function reviewManual(Request $request,PmRun $run,PmRunCheck $check,PmCorrectiveService $correctives): RedirectResponse
    {
        $data=$request->validate([
            'status'=>['required',Rule::in(['pass','warning','fail'])],
            'notes'=>['required','string','max:500'],
        ]);

        DB::transaction(function () use ($request,$run,$check,$data) {
            $item=PmRunCheck::whereKey($check->id)->lockForUpdate()->firstOrFail();
            $lockedRun=PmRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
            if ($lockedRun->review_status!=='draft') {
                throw ValidationException::withMessages(['review'=>'Laporan telah diajukan review atau difinalisasi; checklist terkunci.']);
            }
            if ($item->pm_run_id !== $run->id || $item->method !== 'manual_check'
                || $item->status !== 'pending' || $run->status==='running' || $run->status==='failed') {
                throw ValidationException::withMessages(['status'=>'Checklist ini tidak dapat diperbarui.']);
            }
            $item->update([
                'status'=>$data['status'],'value'=>strtoupper($data['status']),
                'message'=>$data['notes'],'reviewed_by'=>$request->user()->id,'reviewed_at'=>now(),
            ]);
            $counts=PmRunCheck::where('pm_run_id',$run->id)
                ->selectRaw('status, count(*) as total')->groupBy('status')
                ->pluck('total','status')->toArray();
            $run->update([
                'status'=>array_sum(array_map(fn ($state)=>(int)($counts[$state]??0),
                    ['warning','fail','pending','unavailable'])) ? 'needs_review' : 'completed',
                'summary'=>['total'=>array_sum($counts),
                    'systems'=>(int)($run->summary['systems']??0)] + $counts,
            ]);
        });

        $correctives->createFromCheck($check->fresh(),$request->user()->id);
        return redirect()->route('admin.pm.runs.show',$run)
            ->with('success','Checklist manual berhasil dicatat dalam laporan PM.');
    }

    public function execute(Request $request,PmSchedule $schedule,PmExecutionService $executor): RedirectResponse
    {
        // One-off manual execution does not change the schedule or its next automatic slot.
        $run=$executor->start($schedule,'manual',$request->user()->id);
        return redirect()->route('admin.pm.runs.show',$run)
            ->with('success','Pemeriksaan telah dijalankan. Hasil otomatis dan item yang masih perlu diperiksa sudah dicatat.');
    }
}