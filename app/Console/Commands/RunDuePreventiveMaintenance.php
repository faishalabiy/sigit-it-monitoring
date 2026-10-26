<?php

namespace App\Console\Commands;

use App\Models\PmSchedule;
use App\Services\PmExecutionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RunDuePreventiveMaintenance extends Command
{
    protected $signature = 'pm:run-due';
    protected $description = 'Jalankan jadwal PM yang jatuh tempo dan sudah berstatus Siap.';

    public function handle(PmExecutionService $executor): int
    {
        // Persistent heartbeat updated by the actual scheduler invocation, even when no PM is due.
        \Illuminate\Support\Facades\Cache::put('pm:sigit:scheduler-last-tick',time(),now()->addMinutes(30));
        foreach (PmSchedule::where('status','ready')->cursor() as $schedule) {
            $now=Carbon::now($schedule->timezone)->startOfMinute();
            if ($now->toDateString() < $schedule->starts_on->format('Y-m-d')) continue;

            $runTime=substr((string)$schedule->run_time,0,5);
            if ($now->format('H:i') !== $runTime) continue;

            if ($schedule->frequency==='weekly' && (int)$schedule->day_of_week !== $now->dayOfWeek) continue;
            if ($schedule->frequency==='monthly' && (int)$schedule->day_of_month !== $now->day) continue;
            if (! in_array($schedule->frequency,['daily','weekly','monthly'],true)) continue;

            $result=$executor->start($schedule,'automatic',null,$now);
            if ($result) $this->info("PM {$schedule->code}: run #{$result->id} ({$result->status})");
        }
        return self::SUCCESS;
    }
}