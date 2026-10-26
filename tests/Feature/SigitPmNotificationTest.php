<?php

namespace Tests\Feature;

use App\Models\PmCorrectiveAction;
use App\Models\PmNotification;
use App\Models\PmRun;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Models\Role;
use App\Models\User;
use App\Services\PmNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SigitPmNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp():void {
        parent::setUp();
        config()->set('iams.admin_user_ids','');
        config()->set('pm_notifications.email_enabled',false);
        config()->set('mail.default','log');
        Carbon::setTestNow(Carbon::parse('2026-10-22 07:30:00','Asia/Jakarta'));
    }
    protected function tearDown():void {
        Carbon::setTestNow();
        parent::tearDown();
    }
    private function user(array $perms, string $slug):User {
        $user=User::factory()->create();
        $role=Role::create(['name'=>'Role '.$slug,'slug'=>$slug,'permissions'=>$perms]);
        $user->roles()->attach($role);
        return $user;
    }
    private function schedule(User $user):PmSchedule {
        return PmSchedule::create([
            'code'=>'PM-WEEKLY','name'=>'PM Sistem TI',
            'frequency'=>'daily','run_time'=>'08:00',
            'timezone'=>'Asia/Jakarta','starts_on'=>'2026-10-21',
            'day_of_week'=>null,'day_of_month'=>null,
            'assigned_to'=>$user->id,'status'=>'ready',
        ]);
    }

    public function test_upcoming_reminder_created_only_once_per_recipient():void {
        $pic=$this->user(['pm.view'],'technician');
        $admin=$this->user(['pm.view','pm.manage'],'super-admin');
        $schedule=$this->schedule($pic);
        $service=app(PmNotificationService::class);
        $this->assertSame(2,$service->reminders());
        $this->assertSame(0,$service->reminders());
        $this->assertDatabaseCount('pm_notifications',2);
        $notice=PmNotification::where('user_id',$pic->id)->firstOrFail();
        $this->assertSame('pm_reminder',$notice->kind);
        $this->assertSame('disabled',$notice->email_status);
        $this->assertStringContainsString($schedule->code,$notice->message);
        $this->assertNull($notice->read_at);
        $this->assertNotNull(PmNotification::where('user_id',$admin->id)->first());
    }

    public function test_fail_and_overdue_notice_are_deduplicated_with_correct_recipients():void {
        $pic=$this->user(['pm.view'],'planner');
        $admin=$this->user(['pm.view','pm.manage'],'super-admin');
        $schedule=$this->schedule($pic);
        $run=PmRun::create([
            'pm_schedule_id'=>$schedule->id,'schedule_code'=>$schedule->code,
            'schedule_name'=>$schedule->name,'trigger'=>'automatic','status'=>'needs_review',
            'started_at'=>now(),'completed_at'=>now(),
        ]);
        $check=$run->checks()->create([
            'system_code'=>'SIPO','system_name'=>'SIPO',
            'parameter_code'=>'HTTP','parameter_name'=>'Ketersediaan',
            'method'=>'http_status','status'=>'fail',
            'value'=>'503','message'=>'HTTP 503','checked_at'=>now(),
        ]);
        $action=PmCorrectiveAction::create([
            'pm_run_id'=>$run->id,'pm_run_check_id'=>$check->id,
            'status'=>'open','priority'=>'high','finding'=>'App unavailable',
            'assigned_to'=>$pic->id,'due_on'=>'2026-10-19',
        ]);
        $service=app(PmNotificationService::class);
        $this->assertSame(2,$service->failures());
        $this->assertSame(2,$service->overdue());
        $this->assertSame(0,$service->failures());
        $this->assertSame(0,$service->overdue());
        $this->assertDatabaseCount('pm_notifications',4);
        $this->assertTrue(PmNotification::where('kind','corrective_overdue')
            ->where('user_id',$pic->id)->exists());
        $this->assertTrue(PmNotification::where('kind','pm_fail')
            ->where('user_id',$admin->id)->exists());
        $action->update(['status'=>'resolved']);
        $this->travel(1)->days();
        $this->assertSame(0,$service->overdue());
    }

    public function test_missing_backup_is_unavailable_and_weekly_notice_not_a_pass():void {
        $admin=$this->user(['pm.view','pm.manage'],'super-admin');
        $app=PmSystem::create(['code'=>'SIPO','name'=>'SIPO','type'=>'application',
            'status'=>'active','environment'=>'production','criticality'=>'high',
            'target_url'=>'https://sipo.ptrekaindo.co.id']);
        $service=app(PmNotificationService::class);
        config()->set('pm.backup_directories',[]);
        $this->assertSame(1,$service->backups());
        $this->assertSame(0,$service->backups());
        $notice=PmNotification::firstOrFail();
        $this->assertSame('backup',$notice->kind);
        $this->assertStringContainsString('belum dipilih', $notice->message);
        $this->assertSame($admin->id,$notice->user_id);
    }

    public function test_notification_center_is_private_and_read_state_is_scoped():void {
        $alice=$this->user(['pm.view'],'alice');
        $bob=$this->user(['pm.view'],'bob');
        $notice=PmNotification::create([
            'user_id'=>$alice->id,'kind'=>'pm_reminder','level'=>'info',
            'subject'=>'Notifikasi khusus privat','message'=>'Reminder',
            'event_key'=>hash('sha256','test-notice'),
        ]);
        $this->actingAs($bob)->get(route('admin.pm.notifications.index'))
            ->assertOk()->assertDontSee('Notifikasi khusus privat');
        $this->actingAs($bob)->patch(route('admin.pm.notifications.read',$notice))
            ->assertNotFound();
        $this->assertNull($notice->fresh()->read_at);
        $this->actingAs($alice)->get(route('admin.pm.notifications.index'))
            ->assertOk()->assertSee('Notifikasi khusus privat');
        $this->actingAs($alice)->patch(route('admin.pm.notifications.read',$notice))
            ->assertRedirect();
        $this->assertNotNull($notice->fresh()->read_at);
    }

    public function test_email_disabled_with_log_transport_never_sends():void {
        $admin=$this->user(['pm.view','pm.manage'],'super-admin');
        $this->schedule($admin);
        Mail::fake();
        $service=app(PmNotificationService::class);
        $service->reminders();
        $this->assertSame(['sent'=>0,'failed'=>0],$service->deliverPending());
        Mail::assertNothingSent();
        $this->assertSame('disabled',PmNotification::firstOrFail()->email_status);
    }

    public function test_email_delivery_only_when_explicitly_enabled_and_not_log_transport():void {
        $admin=$this->user(['pm.view','pm.manage'],'super-admin');
        $this->schedule($admin);
        Mail::fake();
        config()->set('pm_notifications.email_enabled',true);
        config()->set('mail.default','smtp');
        $service=app(PmNotificationService::class);
        $service->reminders();
        $this->assertSame(['sent'=>1,'failed'=>0],$service->deliverPending());
        $this->assertSame('sent',PmNotification::firstOrFail()->email_status);
        Mail::assertSentCount(1);
        $this->assertSame(['sent'=>0,'failed'=>0],$service->deliverPending());
    }

    public function test_next_due_calculates_weekly_and_monthly_in_schedule_timezone():void {
        $admin=$this->user(['pm.view','pm.manage'],'super-admin');
        $schedule=$this->schedule($admin);
        $schedule->update(['frequency'=>'weekly','day_of_week'=>5,'run_time'=>'08:00']);
        $next=app(PmNotificationService::class)->nextDue($schedule,Carbon::now('Asia/Jakarta'));
        $this->assertSame('2026-10-23 08:00',$next?->format('Y-m-d H:i'));
        $schedule->update(['frequency'=>'monthly','day_of_week'=>null,'day_of_month'=>28]);
        $next=app(PmNotificationService::class)->nextDue($schedule,Carbon::now('Asia/Jakarta'));
        $this->assertSame('2026-10-28 08:00',$next?->format('Y-m-d H:i'));
    }
}
