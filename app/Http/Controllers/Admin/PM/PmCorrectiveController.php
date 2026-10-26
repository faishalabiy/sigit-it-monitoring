<?php

namespace App\Http\Controllers\Admin\PM;

use App\Http\Controllers\Controller;
use App\Models\PmCorrectiveAction;
use App\Models\PmRun;
use App\Models\PmRunCheck;
use App\Models\User;
use App\Services\PmCorrectiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PmCorrectiveController extends Controller
{
    public function index(Request $request): View
    {
        $filters=$request->validate([
            'status'=>['nullable',Rule::in(['open','in_progress','resolved'])],
            'priority'=>['nullable',Rule::in(['medium','high'])],
        ]);
        $actions=PmCorrectiveAction::with(['assignee','check','run'])
            ->when($filters['status']??null,fn ($q,$v)=>$q->where('status',$v))
            ->when($filters['priority']??null,fn ($q,$v)=>$q->where('priority',$v))
            ->orderByRaw("CASE WHEN status = 'resolved' THEN 1 ELSE 0 END")
            ->orderBy('due_on')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('admin.pm.correctives.index',compact('actions'));
    }

    public function show(PmCorrectiveAction $action): View
    {
        $action->load(['check','run','assignee','resolver',
            'events'=>fn($q)=>$q->with('actor')->orderBy('id')]);
        return view('admin.pm.correctives.show',[
            'action'=>$action,'staff'=>User::orderBy('name')->get(['id','name']),
        ]);
    }

    public function createFromCheck(
        Request $request,PmRun $run,PmRunCheck $check,PmCorrectiveService $service
    ): RedirectResponse {
        abort_unless($check->pm_run_id===$run->id,404);
        if (! in_array($check->status,['warning','fail','unavailable'],true)) {
            throw ValidationException::withMessages(['check'=>'Hanya hasil WARNING, FAIL, atau BELUM TERSEDIA yang dapat ditindaklanjuti.']);
        }
        $action=$service->createFromCheck($check,$request->user()->id,true);
        return redirect()->route('admin.pm.correctives.show',$action)
            ->with('success','Tindak lanjut berhasil dicatat.');
    }

    public function update(Request $request,PmCorrectiveAction $action): RedirectResponse
    {
        $data=$request->validate([
            'status'=>['required',Rule::in(['open','in_progress','resolved'])],
            'priority'=>['required',Rule::in(['medium','high'])],
            'assigned_to'=>['nullable','integer','exists:users,id'],
            'due_on'=>['nullable','date'],
            'recommendation'=>['nullable','string','max:3000'],
            'resolution_notes'=>['nullable','string','max:3000',
                Rule::requiredIf(fn()=> $request->input('status')==='resolved')],
        ]);
        DB::transaction(function () use ($action,$data,$request) {
            $record=PmCorrectiveAction::whereKey($action->id)->lockForUpdate()->firstOrFail();
            $previous=$record->only(['status','priority','assigned_to','due_on','recommendation','resolution_notes']);
            $oldStatus=$record->status;
            $newStatus=$data['status'];
            $values=[
                'status'=>$newStatus,
                'priority'=>$data['priority'],
                'assigned_to'=>$data['assigned_to']??null,
                'due_on'=>$data['due_on']??null,
                'recommendation'=>$data['recommendation']??null,
                'resolution_notes'=>$newStatus==='resolved' ? $data['resolution_notes'] : null,
            ];
            if ($newStatus==='resolved') {
                $values['resolved_by']=$record->status==='resolved'
                    ? ($record->resolved_by??$request->user()->id)
                    : $request->user()->id;
                $values['resolved_at']=$record->status==='resolved'
                    ? ($record->resolved_at??now()) : now();
            } else {
                $values['resolved_by']=null;
                $values['resolved_at']=null;
            }
            $record->update($values);
            $changed=[];
            foreach (array_keys($previous) as $key) {
                $old=$key==='due_on' ? $previous[$key]?->format('Y-m-d') : $previous[$key];
                $new=$key==='due_on' ? $record->due_on?->format('Y-m-d') : $record->{$key};
                if ((string)($old??'') !== (string)($new??'')) {
                    $changed[$key]=['from'=>$old,'to'=>$new];
                }
            }
            if ($changed) {
                $record->events()->create([
                    'actor_id'=>$request->user()->id,
                    'event'=>$newStatus==='resolved'?'resolved':($oldStatus==='resolved' && $newStatus!=='resolved'?'reopened':'updated'),
                    'changes'=>$changed,
                ]);
            }
        });
        return redirect()->route('admin.pm.correctives.show',$action)
            ->with('success','Tindak lanjut telah diperbarui.');
    }
}