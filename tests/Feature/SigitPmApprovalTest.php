<?php
namespace Tests\Feature;

use App\Models\PmRun;
use App\Models\PmSchedule;
use App\Models\PmSystem;
use App\Models\PmParameterTemplate;
use App\Models\Role;
use App\Models\User;
use App\Services\PmReportWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigitPmApprovalTest extends TestCase {
 use RefreshDatabase;
 protected function setUp():void{
  parent::setUp();config()->set('iams.admin_user_ids','');
  config()->set('excel.temporary_files.local_path',sys_get_temp_dir());
 }
 private function user(array $perms):User{
  $user=User::factory()->create();
  $role=Role::create(['name'=>'Test '.uniqid(),'slug'=>'test-'.uniqid(),'permissions'=>$perms]);
  $user->roles()->attach($role);return $user;
 }
 private function makeRun(array $statuses=['pass','warning']):PmRun{
  $schedule=PmSchedule::create(['code'=>'PM-APPROVAL','name'=>'PM Review','frequency'=>'daily',
   'run_time'=>'08:00','starts_on'=>'2026-10-01','status'=>'ready','timezone'=>'Asia/Jakarta']);
  $run=PmRun::create(['pm_schedule_id'=>$schedule->id,'schedule_code'=>'PM-APPROVAL',
   'schedule_name'=>'PM Review','status'=>'needs_review','trigger'=>'automatic',
   'started_at'=>now(),'completed_at'=>now()]);
  foreach($statuses as $i=>$status){
   $run->checks()->create(['system_code'=>'SYS001','system_name'=>'SIGIT',
    'parameter_code'=>'PARAM'.$i,'parameter_name'=>$status==='warning'?'Disk':('Check '.$i),
    'method'=>$status==='pending'?'manual_check':'disk_usage',
    'status'=>$status,'value'=>(string)(65+$i*20),'unit'=>'%',
    'message'=>'Monitoring valid','checked_at'=>now(),'thresholds'=>['warning_max'=>80]]);
  }
  return $run;
 }
 public function test_review_requires_completed_manual_check_and_unavailable_explanation():void{
  $manager=$this->user(['pm.view','pm.manage']);
  $run=$this->makeRun(['pass','pending','unavailable']);
  $this->actingAs($manager)->post(route('admin.pm.runs.approval',[$run,'submit']),
   ['notes'=>'Pemeriksaan lain tidak tersedia karena agen belum dikonfigurasi.'])
   ->assertSessionHasErrors('review');
  $run->checks()->where('status','pending')->update(['status'=>'pass']);
  $this->actingAs($manager)->post(route('admin.pm.runs.approval',[$run,'submit']),['notes'=>'kurang'])
   ->assertSessionHasErrors('notes');
  $this->actingAs($manager)->post(route('admin.pm.runs.approval',[$run,'submit']),[
   'notes'=>'Monitoring database belum terhubung ke health endpoint server.'])
   ->assertRedirect(route('admin.pm.runs.show',$run));
  $this->assertSame('under_review',$run->fresh()->review_status);
 }
 public function test_submit_approve_finalize_export_and_immutable_snapshot():void{
  $manager=$this->user(['pm.view','pm.manage']);
  $reviewer=$this->user(['pm.view','pm.approve']);
  $run=$this->makeRun();
  $this->actingAs($manager)->post(route('admin.pm.runs.approval',[$run,'submit']),['notes'=>'Semua pemeriksaan selesai.'])
   ->assertRedirect();
  $this->actingAs($manager)->post(route('admin.pm.runs.approval',[$run,'approve']),[])->assertForbidden();
  $this->actingAs($reviewer)->post(route('admin.pm.runs.approval',[$run,'approve']),['notes'=>'Disetujui secara teknis.'])
   ->assertRedirect();
  $this->actingAs($reviewer)->post(route('admin.pm.runs.approval',[$run,'finalize']),[])
   ->assertRedirect();
  $run->refresh();
  $this->assertSame('finalized',$run->review_status);
  $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/',$run->report_sha256);
  $this->assertSame(3,$run->approvalEvents()->count());
  $this->assertSame('SIGIT',app(PmReportWorkflowService::class)->verifiedSnapshot($run)['checks'][0]['system_name']);
  $pdf=$this->actingAs($manager)->get(route('admin.pm.runs.pdf',$run));
  $pdf->assertOk()->assertHeader('content-type','application/pdf');
  $this->assertStringStartsWith('%PDF-', $pdf->getContent());
  $xlsx=$this->actingAs($manager)->get(route('admin.pm.runs.excel',$run));
  $xlsx->assertOk();
  $this->assertStringContainsString('spreadsheetml',$xlsx->headers->get('content-type'));
  $run->checks()->first()->update(['value'=>'999','status'=>'fail']);
  $this->assertSame('65',app(PmReportWorkflowService::class)->verifiedSnapshot($run->fresh())['checks'][0]['value']);
  $this->actingAs($reviewer)->post(route('admin.pm.runs.approval',[$run,'finalize']),[])
   ->assertSessionHasErrors('review');
 }
 public function test_self_approval_is_denied_and_rejection_returns_to_draft():void {
  $self=$this->user(['pm.view','pm.manage','pm.approve']);
  $reviewer=$this->user(['pm.approve','pm.view']);
  $run=$this->makeRun();
  $this->actingAs($self)->post(route('admin.pm.runs.approval',[$run,'submit']),[])->assertRedirect();
  $this->actingAs($self)->post(route('admin.pm.runs.approval',[$run,'approve']),[])
   ->assertSessionHasErrors('review');
  $this->actingAs($reviewer)->post(route('admin.pm.runs.approval',[$run,'reject']),['notes'=>'kurang'])
   ->assertSessionHasErrors('notes');
  $this->actingAs($reviewer)->post(route('admin.pm.runs.approval',[$run,'reject']),
   ['notes'=>'Bukti hasil monitoring perlu penjelasan tambahan.'])->assertRedirect();
  $this->assertSame('draft',$run->fresh()->review_status);
 }
 public function test_pdf_excel_restricted_to_finalized_and_role():void {
  $viewer=$this->user(['pm.view']);$noPm=$this->user(['assets.view']);
  $run=$this->makeRun();
  $this->actingAs($viewer)->get(route('admin.pm.runs.pdf',$run))->assertStatus(409);
  $this->actingAs($viewer)->get(route('admin.pm.runs.excel',$run))->assertStatus(409);
  $this->actingAs($noPm)->get(route('admin.pm.runs.pdf',$run))->assertForbidden();
  $this->actingAs($noPm)->get(route('admin.pm.runs.excel',$run))->assertForbidden();
 }
 public function test_report_integrity_hash_blocks_export_after_snapshot_tamper():void {
  $requester=$this->user(['pm.view','pm.manage']);
  $approver=$this->user(['pm.view','pm.approve']);
  $run=$this->makeRun(['pass']);
  $this->actingAs($requester)->post(route('admin.pm.runs.approval',[$run,'submit']),[])->assertRedirect();
  $this->actingAs($approver)->post(route('admin.pm.runs.approval',[$run,'approve']),[])->assertRedirect();
  $this->actingAs($approver)->post(route('admin.pm.runs.approval',[$run,'finalize']),[])->assertRedirect();
  $run->refresh();
  $run->update(['report_snapshot'=>str_replace('SIGIT','FAKE',$run->report_snapshot)]);
  $this->actingAs($approver)->get(route('admin.pm.runs.pdf',$run))->assertStatus(409);
  $this->actingAs($approver)->get(route('admin.pm.runs.excel',$run))->assertStatus(409);
 }
 public function test_approver_may_not_be_original_manual_execution_actor():void {
  $requester=$this->user(['pm.view','pm.manage']);
  $executor=$this->user(['pm.view','pm.approve']);
  $run=$this->makeRun(['pass']);
  $run->update(['initiated_by'=>$executor->id]);
  $this->actingAs($requester)->post(route('admin.pm.runs.approval',[$run,'submit']),[])->assertRedirect();
  $this->actingAs($executor)->post(route('admin.pm.runs.approval',[$run,'approve']),[])
    ->assertSessionHasErrors('review');
  $this->assertSame('under_review',$run->fresh()->review_status);
 }

 public function test_manual_check_is_locked_after_submission():void {
  $manager=$this->user(['pm.view','pm.manage']);
  $run=$this->makeRun(['pass','pending']);
  $run->checks()->where('status','pending')->update(['status'=>'pass']);
  $this->actingAs($manager)->post(route('admin.pm.runs.approval',[$run,'submit']),[])->assertRedirect();
  // Attempted tamper with a pending manual check after the submission.
  $check=$run->checks()->where('method','manual_check')->firstOrFail();
  $check->update(['status'=>'pending']);
  $this->actingAs($manager)->patch(route('admin.pm.runs.checks.review',[$run,$check]),[
   'status'=>'pass','notes'=>'Trying to change submitted review.'])->assertSessionHasErrors('review');
 }
}