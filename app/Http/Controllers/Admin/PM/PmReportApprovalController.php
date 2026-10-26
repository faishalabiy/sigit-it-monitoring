<?php
namespace App\Http\Controllers\Admin\PM;

use App\Exports\PmReportExport;
use App\Http\Controllers\Controller;
use App\Models\PmRun;
use App\Services\PmReportWorkflowService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PmReportApprovalController extends Controller {
 public function transition(Request $request,PmRun $run,PmReportWorkflowService $workflow,string $event):RedirectResponse {
  abort_unless(in_array($event,['submit','approve','reject','finalize'],true),404);
  if(in_array($event,['approve','reject','finalize'],true))
   abort_unless($request->user()->hasPermission('pm.approve'),403);
  else abort_unless($request->user()->hasPermission('pm.manage'),403);
  $data=$request->validate(['notes'=>['nullable','string','max:3000']]);
  $workflow->transition($run,$request->user(),$event,$data['notes']??null);
  return redirect()->route('admin.pm.runs.show',$run)
   ->with('success',match($event){
    'submit'=>'Laporan diajukan untuk review.',
    'approve'=>'Laporan telah disetujui.',
    'reject'=>'Laporan dikembalikan untuk diperbaiki.',
    'finalize'=>'Laporan final dibekukan dan siap diunduh.',
   });
 }
 public function pdf(PmRun $run,PmReportWorkflowService $workflow) {
  $report=$workflow->verifiedSnapshot($run);
  return Pdf::loadView('admin.pm.runs.pdf',['report'=>$report,'checksum'=>$run->report_sha256])
   ->setPaper('a4','landscape')->download('SIGIT-PM-'.str_pad((string)$run->id,5,'0',STR_PAD_LEFT).'.pdf');
 }
 public function excel(PmRun $run,PmReportWorkflowService $workflow) {
  $report=$workflow->verifiedSnapshot($run);
  return Excel::download(new PmReportExport($report),
   'SIGIT-PM-'.str_pad((string)$run->id,5,'0',STR_PAD_LEFT).'.xlsx');
 }
}