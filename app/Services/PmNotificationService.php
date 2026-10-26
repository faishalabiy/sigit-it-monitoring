<?php

namespace App\Services;

use App\Mail\PmAlertMail;
use App\Models\PmCorrectiveAction;
use App\Models\PmNotification;
use App\Models\PmRunCheck;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PmNotificationService
{
    public function __construct(private readonly PmProbeService $probe) {}

    public function scan(): array
    {
        return [
            'failures'=>$this->failures(),
            'reminders'=>$this->reminders(),
            'overdue'=>$this->overdue(),
            'backup'=>$this->backups(),
        ];
    }

    private function fallbackRecipients(): array
    {
        $ids=User::whereHas('roles',fn($q)=>$q->where('slug','super-admin'))
            ->pluck('users.id')->all();
        foreach (explode(',',(string)config('iams.admin_user_ids','')) as $raw) {
            if (ctype_digit(trim($raw))) $ids[]=(int)trim($raw);
        }
        return array_values(array_unique(array_map('intval',$ids)));
    }

    private function recipients(?int $primary): array
    {
        // Assigned PIC receives the notice and super admins receive escalation copy.
        return array_values(array_unique(array_merge($primary?[$primary]:[],$this->fallbackRecipients())));
    }

    private function createNotices(string $event, array $users, string $kind,
        string $level, string $subject, string $message, string $url): int
    {
        $count=0;
        foreach ($users as $userId) {
            if (! User::whereKey($userId)->exists()) continue;
            $key=hash('sha256',$event);
            $notice=PmNotification::firstOrCreate(
                ['user_id'=>$userId,'event_key'=>$key],
                [
                    'kind'=>$kind,'level'=>$level,'subject'=>$subject,
                    'message'=>$message,'url'=>$url,
                    'email_status'=>$this->emailAvailable()?'pending':'disabled',
                ]
            );
            if ($notice->wasRecentlyCreated) $count++;
        }
        return $count;
    }

    public function failures(): int
    {
        $created=0;
        $checks=PmRunCheck::where('status','fail')
            ->whereHas('run', fn($q)=>$q->whereNotIn('status',['running','failed'])
                ->where('started_at','>=',now()->subDays(
                    (int)config('pm_notifications.scan_lookback_days',7))))
            ->with('run.schedule')->get();
        foreach ($checks as $check) {
            $run=$check->run;
            $users=$this->recipients($run->schedule?->assigned_to);
            $created+=$this->createNotices('pm-fail:'.$check->id,$users,'pm_fail','danger',
                'PM FAIL — '.$check->system_name,
                'Parameter '.$check->parameter_name.' pada PM #'.$run->id.
                    ' berstatus FAIL. Tinjau hasil, verifikasi penyebab, dan tindak lanjut corrective.',
                route('admin.pm.runs.show',$run,false));
        }
        return $created;
    }

    public function reminders(): int
    {
        $created=0;
        foreach (PmSchedule::where('status','ready')->get() as $schedule) {
            $zone=$schedule->timezone?:'Asia/Jakarta';
            $now=Carbon::now($zone);
            $next=$this->nextDue($schedule,$now);
            if (!$next || $next->lte($now) ||
                $next->gt($now->copy()->addHours((int)config('pm_notifications.reminder_hours',24)))) continue;
            $created+=$this->createNotices(
                'pm-remind:'.$schedule->id.':'.$next->format('YmdHi'),
                $this->recipients($schedule->assigned_to),'pm_reminder','info',
                'Pengingat PM — '.$schedule->name,
                'Jadwal PM '.$schedule->code.' akan dilaksanakan pada '.
                    $next->format('d-m-Y H:i').' '.$zone.'. Periksa kesiapan target dan checklist.',
                route('admin.pm.schedules.index',[],false));
        }
        return $created;
    }

    public function nextDue(PmSchedule $schedule, Carbon $now): ?Carbon
    {
        $zone=$schedule->timezone?:'Asia/Jakarta';
        $start=$schedule->starts_on?->format('Y-m-d');
        if (!$start) return null;
        for($offset=0;$offset<=35;$offset++) {
            $date=$now->copy()->startOfDay()->addDays($offset);
            if($date->format('Y-m-d')<$start) continue;
            $frequency=$schedule->frequency;
            if($frequency==='weekly' && (int)$schedule->day_of_week!==$date->dayOfWeek) continue;
            if($frequency==='monthly' && (int)$schedule->day_of_month!==$date->day) continue;
            if(!in_array($frequency,['daily','weekly','monthly'],true)) continue;
            $candidate=Carbon::parse($date->format('Y-m-d').' '.substr((string)$schedule->run_time,0,5),$zone);
            if($candidate->gt($now)) return $candidate;
        }
        return null;
    }

    public function overdue(): int
    {
        $created=0;
        $date=Carbon::today('Asia/Jakarta');
        foreach (PmCorrectiveAction::where('status','!=','resolved')
            ->whereDate('due_on','<',$date->toDateString())->get() as $action) {
            $created+=$this->createNotices(
                'pm-overdue:'.$action->id.':'.$date->format('Ymd'),
                $this->recipients($action->assigned_to),'corrective_overdue','danger',
                'Corrective terlambat — CA-'.str_pad((string)$action->id,5,'0',STR_PAD_LEFT),
                'Tindak lanjut PM telah melewati tenggat '.$action->due_on?->format('d-m-Y').
                    '. Periksa progres dan lakukan eskalasi bila diperlukan.',
                route('admin.pm.correctives.show',$action,false));
        }
        return $created;
    }

    public function backups(): int
    {
        if(!config('pm_notifications.backup_scan_enabled',true)) return 0;
        $created=0;
        $today=Carbon::today('Asia/Jakarta');
        foreach(PmSystem::where('status','active')->where('type','application')
            ->with(['parameters'=>fn($q)=>$q->where('is_active',true)
                ->where('method','backup_age')])->get() as $system) {
            $recipient=$this->recipients($system->owner_id);
            if(!$recipient) continue;
            $param=$system->parameters->first();
            if($param) {
                $result=$this->probe->check($system,$param);
            } else {
                $result=['status'=>'unavailable',
                    'message'=>'Template usia backup belum dipilih untuk sistem ini.'];
            }
            if(!in_array($result['status'],['warning','fail','unavailable'],true)) continue;
            $weekly=$result['status']==='unavailable';
            $period=$weekly?$today->copy()->startOfWeek()->format('oW'):$today->format('Ymd');
            $created+=$this->createNotices(
                'pm-backup:'.$system->id.':'.$result['status'].':'.$period,
                $recipient,'backup','warning',
                'Bukti backup '.strtoupper($result['status']).' — '.$system->name,
                'Kondisi pemantauan backup '.$system->name.': '.$result['message'].
                    ' Verifikasi sumber backup dan lakukan uji restore terpisah.',
                route('admin.pm.systems.edit',$system,false));
        }
        return $created;
    }

    public function deliverPending(int $limit=25): array
    {
        if(!$this->emailAvailable()) return ['sent'=>0,'failed'=>0];
        $result=['sent'=>0,'failed'=>0];
        $notices=PmNotification::with('user')
            ->whereIn('email_status',['pending','failed'])
            ->where('email_attempts','<',3)
            ->where(fn($q)=>$q->whereNull('email_attempted_at')
                ->orWhere('email_attempted_at','<',now()->subMinutes(20)))
            ->orderBy('id')->limit($limit)->get();
        foreach($notices as $notice) {
            $notice->email_attempts++;
            $notice->email_attempted_at=now();
            $notice->save();
            $email=$notice->user?->email;
            if(!is_string($email) || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
                $notice->update(['email_status'=>'failed']);$result['failed']++;continue;
            }
            try {
                Mail::to($email)->send(new PmAlertMail(
                    $notice->subject,$notice->message,config('app.url').$notice->url
                ));
                $notice->update(['email_status'=>'sent','email_sent_at'=>now()]);
                $result['sent']++;
            } catch(Throwable $error) {
                report($error);
                $notice->update(['email_status'=>'failed']);
                $result['failed']++;
            }
        }
        return $result;
    }

    private function emailAvailable():bool
    {
        return (bool)config('pm_notifications.email_enabled',false)
            && !in_array(config('mail.default'),['log','array'],true);
    }
}