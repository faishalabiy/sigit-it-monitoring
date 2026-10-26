<?php

namespace App\Services;

use App\Models\PmRun;
use App\Models\PmSchedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class PmExecutionService
{
    public function __construct(private readonly PmProbeService $probe, private readonly PmCorrectiveService $correctives) {}

    /**
     * Manual runs are always new. Automatic runs are deduplicated per scheduled slot.
     * Reserving the run is transactional; a second request cannot start while running.
     */
    public function start(PmSchedule $schedule, string $trigger, ?int $userId=null, ?CarbonInterface $slot=null): ?PmRun
    {
        if (! in_array($trigger,['manual','automatic'],true)) throw new \InvalidArgumentException('Invalid trigger');

        $run = DB::transaction(function () use ($schedule,$trigger,$userId,$slot) {
            $locked=PmSchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'ready') {
                if ($trigger==='automatic') return null;
                throw ValidationException::withMessages(['schedule'=>'Jadwal harus berstatus Siap sebelum dijalankan.']);
            }

            // Aborted PHP/web workers must not block all later PM executions forever.
            PmRun::where('pm_schedule_id',$locked->id)
                ->where('status','running')->where('started_at','<',now()->subMinutes(15))
                ->update(['status'=>'failed','completed_at'=>now(),
                    'summary'=>json_encode(['message'=>'Eksekusi sebelumnya terhenti/tidak selesai.'])]);

            if (PmRun::where('pm_schedule_id',$locked->id)->where('status','running')->exists()) {
                if ($trigger==='automatic') return null;
                throw ValidationException::withMessages(['schedule'=>'Pelaksanaan sebelumnya masih berjalan.']);
            }

            if ($trigger==='automatic' && $slot) {
                $exists=PmRun::where('pm_schedule_id',$locked->id)
                    ->where('trigger','automatic')
                    ->where('scheduled_for',$slot->copy()->utc()->format('Y-m-d H:i:s'))
                    ->exists();
                if ($exists) return null;
            }

            if (! $locked->systems()->where('status','active')->exists()) {
                if ($trigger==='automatic') return null;
                throw ValidationException::withMessages(['schedule'=>'Jadwal belum memiliki target sistem aktif.']);
            }

            return PmRun::create([
                'pm_schedule_id'=>$locked->id,'schedule_code'=>$locked->code,
                'schedule_name'=>$locked->name,'trigger'=>$trigger,
                'initiated_by'=>$userId,
                'scheduled_for'=>$slot?->copy()->utc(),
                'status'=>'running','started_at'=>now(),
            ]);
        });

        if (! $run) return null;

        try {
            $systems=$schedule->systems()->where('status','active')->with([
                'parameters'=>fn ($q)=>$q->where('is_active',true),
            ])->get();
            $count=0;
            foreach ($systems as $system) {
                foreach ($system->parameters as $parameter) {
                    $result=$this->probe->check($system,$parameter);
                    $run->checks()->create([
                        'pm_system_id'=>$system->id,
                        'pm_parameter_template_id'=>$parameter->id,
                        'system_code'=>$system->code,'system_name'=>$system->name,
                        'parameter_code'=>$parameter->code,'parameter_name'=>$parameter->name,
                        'method'=>$parameter->method,'status'=>$result['status'],
                        'value'=>$result['value'] ?? null,'unit'=>$parameter->unit,
                        'message'=>$result['message'],
                        'checked_at'=>now(),
                        'thresholds'=>[
                            'expected'=>$parameter->expected_value,
                            'warning_min'=>$parameter->warning_min,
                            'warning_max'=>$parameter->warning_max,
                            'failure_min'=>$parameter->failure_min,
                            'failure_max'=>$parameter->failure_max,
                        ],
                    ]);
                    $count++;
                }
            }

            if ($count===0) {
                $run->update(['status'=>'failed','completed_at'=>now(),
                    'summary'=>['total'=>0,'message'=>'Tidak ada parameter aktif pada target jadwal.']]);
                return $run;
            }

            $counts=$run->checks()->selectRaw('status, COUNT(*) AS total')->groupBy('status')
                ->pluck('total','status')->toArray();
            $summary=['total'=>$count,'systems'=>$systems->count()] + $counts;
            $needsReview=array_sum(array_map(fn ($key)=>(int)($counts[$key]??0),
                ['warning','fail','pending','unavailable']))>0;
            $run->update([
                'status'=>$needsReview ? 'needs_review':'completed',
                'completed_at'=>now(),'summary'=>$summary,
            ]);
            // Each WARNING/FAIL is tracked independently; no automatic remediation.
            $this->correctives->createForRun($run->fresh());
        } catch (Throwable $e) {
            report($e);
            $run->update(['status'=>'failed','completed_at'=>now(),
                'summary'=>['message'=>'Proses pemeriksaan terganggu; hasil parsial dapat dilihat.']]);
        }

        // Record FAIL alerts promptly for both manual and scheduled PM.
        // Notification problems must never rewrite a completed PM result.
        try {
            app(PmNotificationService::class)->failures();
        } catch (Throwable $exception) {
            report($exception);
        }

        return $run->fresh();
    }
}