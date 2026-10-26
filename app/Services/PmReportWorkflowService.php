<?php
namespace App\Services;
use App\Models\PmRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PmReportWorkflowService {
 public function transition(PmRun $run,User $actor,string $event,?string $notes=null):PmRun {
  return DB::transaction(function()use($run,$actor,$event,$notes){
   $run=PmRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
   if($run->review_status==='finalized') throw ValidationException::withMessages(['review'=>'Laporan final tidak dapat diubah.']);
   if(in_array($run->status,['running','failed'],true) || !$run->checks()->exists())
    throw ValidationException::withMessages(['review'=>'Pelaksanaan belum selesai atau tidak memiliki pemeriksaan.']);
   if($event==='submit'){
    $this->requireState($run,'draft');
    if($run->checks()->where('status','pending')->exists())
     throw ValidationException::withMessages(['review'=>'Checklist manual harus diselesaikan sebelum review.']);
    if($run->checks()->where('status','unavailable')->exists() && mb_strlen(trim((string)$notes))<15)
     throw ValidationException::withMessages(['notes'=>'Jelaskan pengecualian item Belum Tersedia (min. 15 karakter).']);
    $run->fill(['review_status'=>'under_review','review_requested_by'=>$actor->id,'review_requested_at'=>now(),
     'review_notes'=>$notes,'approved_by'=>null,'approved_at'=>null,'approval_notes'=>null]);
   } elseif($event==='approve'){
    $this->requireState($run,'under_review');
    if($run->review_requested_by===$actor->id || $run->initiated_by===$actor->id)
     throw ValidationException::withMessages(['review'=>'Pengaju/pelaksana tidak dapat menyetujui laporan sendiri.']);
    $run->fill(['review_status'=>'approved','approved_by'=>$actor->id,'approved_at'=>now(),'approval_notes'=>$notes]);
   } elseif($event==='reject'){
    $this->requireState($run,'under_review');
    if(mb_strlen(trim((string)$notes))<10)
     throw ValidationException::withMessages(['notes'=>'Alasan pengembalian minimal 10 karakter.']);
    $run->fill(['review_status'=>'draft','review_requested_by'=>null,'review_requested_at'=>null,
     'review_notes'=>null,'approved_by'=>null,'approved_at'=>null,'approval_notes'=>null]);
   } elseif($event==='finalize'){
    $this->requireState($run,'approved');
    if($run->approved_by!==$actor->id)
     throw ValidationException::withMessages(['review'=>'Hanya pemberi approval dapat memfinalisasi.']);
    $run->fill(['review_status'=>'finalized','finalized_by'=>$actor->id,'finalized_at'=>now()]);
    $json=json_encode($this->snapshot($run,$actor),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $run->report_snapshot=$json;
    $run->report_sha256=hash('sha256',$json);
   } else throw new \InvalidArgumentException('Invalid approval transition');
   $run->save();
   $run->approvalEvents()->create(['actor_id'=>$actor->id,'event'=>$event,'notes'=>$notes]);
   return $run->fresh();
  });
 }
 public function verifiedSnapshot(PmRun $run):array {
  if($run->review_status!=='finalized' || !is_string($run->report_snapshot)
   || !is_string($run->report_sha256)
   || !hash_equals($run->report_sha256,hash('sha256',$run->report_snapshot)))
    abort(409,'Laporan belum final atau integritas dokumen tidak cocok.');
  return json_decode($run->report_snapshot,true,512,JSON_THROW_ON_ERROR);
 }
 private function snapshot(PmRun $run,User $finalizer):array {
  $run->load(['initiator','reviewRequester','approver',
   'checks'=>fn($q)=>$q->orderBy('system_code')->orderBy('id'),
   'correctives'=>fn($q)=>$q->with(['assignee','check'])->orderBy('id')]);
  $checks=$run->checks->map(fn($c)=>[
   'system_code'=>$c->system_code,'system_name'=>$c->system_name,
   'parameter_code'=>$c->parameter_code,'parameter_name'=>$c->parameter_name,
   'method'=>$c->method,'status'=>$c->status,'value'=>$c->value,'unit'=>$c->unit,
   'message'=>$c->message,'thresholds'=>$c->thresholds,
   'checked_at'=>$c->checked_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
   'reviewed_at'=>$c->reviewed_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
  ])->all();
  $counts=array_count_values(array_column($checks,'status'));
  $actions=$run->correctives->map(fn($a)=>[
   'code'=>'CA-'.str_pad((string)$a->id,5,'0',STR_PAD_LEFT),
   'system_name'=>$a->check?->system_name,'parameter_name'=>$a->check?->parameter_name,
   'finding'=>$a->finding,'priority'=>$a->priority,'recommendation'=>$a->recommendation,
   'status'=>$a->status,'assigned_to'=>$a->assignee?->name,
   'due_on'=>$a->due_on?->format('Y-m-d'),'resolution_notes'=>$a->resolution_notes,
  ])->all();
  return [
   'document_number'=>sprintf('REKA/SIGIT/PM/%s/%05d',
    $run->started_at->timezone('Asia/Jakarta')->format('Y'),$run->id),
   'run_id'=>$run->id,'schedule_code'=>$run->schedule_code,'schedule_name'=>$run->schedule_name,
   'trigger'=>$run->trigger,
   'started_at'=>$run->started_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
   'completed_at'=>$run->completed_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
   'execution_status'=>$run->status,'summary'=>[
    'total'=>count($checks),'systems'=>count(array_unique(array_column($checks,'system_code'))),
    'pass'=>$counts['pass']??0,'warning'=>$counts['warning']??0,
    'fail'=>$counts['fail']??0,'pending'=>$counts['pending']??0,
    'unavailable'=>$counts['unavailable']??0,
   ],
   'prepared_by'=>$run->reviewRequester?->name,
   'performed_by'=>$run->initiator?->name??'Laravel Scheduler',
   'review_notes'=>$run->review_notes,
   'approved_by'=>$run->approver?->name,
   'approved_at'=>$run->approved_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
   'approval_notes'=>$run->approval_notes,'finalized_by'=>$finalizer->name,
   'finalized_at'=>$run->finalized_at?->timezone('Asia/Jakarta')->format('Y-m-d H:i:s'),
   'checks'=>$checks,'correctives'=>$actions,
  ];
 }
 private function requireState(PmRun $run,string $state):void {
  if($run->review_status!==$state) throw ValidationException::withMessages(['review'=>'Tahapan laporan tidak sesuai.']);
 }
}