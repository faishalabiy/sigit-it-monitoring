<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class PmReportExport implements WithMultipleSheets {
 public function __construct(private readonly array $report) {}
 public function sheets():array {
  $r=$this->report;
  return [
   new PmReportArraySheet('Ringkasan',[
    ['LAPORAN PREVENTIVE MAINTENANCE SIGIT'],
    ['Nomor Dokumen',$r['document_number']],['Jadwal',$r['schedule_name']],
    ['Kode Jadwal',$r['schedule_code']],['Pelaksanaan',$r['started_at']],
    ['Selesai Pemeriksaan',$r['completed_at']],['Hasil Pemeriksaan Saat Pelaksanaan',match($r['execution_status']) {
     'completed'=>'Semua Normal','needs_review'=>'Ada Temuan',
     'failed'=>'Pemeriksaan Gagal',default=>'Sedang Berjalan',
    }],
    ['Petugas',$r['performed_by']],['Pengaju',$r['prepared_by']],
    ['Catatan Review',$r['review_notes']],['Penyetuju',$r['approved_by']],
    ['Persetujuan',$r['approved_at']],['Catatan Approval',$r['approval_notes']],
    ['Finalisasi Oleh',$r['finalized_by']],['Tanggal Final',$r['finalized_at']],
    ['Total Parameter',$r['summary']['total']],
    ['PASS',$r['summary']['pass']],['WARNING',$r['summary']['warning']],
    ['FAIL',$r['summary']['fail']],['BELUM TERSEDIA',$r['summary']['unavailable']],
   ]),
   new PmReportArraySheet('Pemeriksaan',array_merge(
    [['Sistem','Kode Sistem','Parameter','Kode Parameter','Metode','Nilai','Unit','Status',
     'Nilai Acuan','WARNING Min','WARNING Max','FAIL Min','FAIL Max','Waktu Periksa','Keterangan']],
    array_map(fn($c)=>[
     $c['system_name'],$c['system_code'],$c['parameter_name'],$c['parameter_code'],$c['method'],
     $c['value'],$c['unit'],$c['status'],
     $c['thresholds']['expected']??null,$c['thresholds']['warning_min']??null,
     $c['thresholds']['warning_max']??null,$c['thresholds']['failure_min']??null,
     $c['thresholds']['failure_max']??null,$c['checked_at'],$c['message'],
    ],$r['checks'])
   )),
   new PmReportArraySheet('Tindak Lanjut',array_merge(
    [['Nomor CA','Sistem','Parameter','Temuan','Prioritas','Rekomendasi','Status saat Final',
      'PIC saat Final','Tenggat','Catatan Penyelesaian']],
    array_map(fn($a)=>[
     $a['code'],$a['system_name'],$a['parameter_name'],$a['finding'],$a['priority'],
     $a['recommendation'],$a['status'],$a['assigned_to'],$a['due_on'],$a['resolution_notes'],
    ],$r['correctives'])
   )),
  ];
 }
}

class PmReportArraySheet implements FromArray,WithTitle,ShouldAutoSize {
 public function __construct(private readonly string $name,private readonly array $rows){}
 public function title():string{return $this->name;}
 public function array():array {
  return array_map(fn($row)=>array_map(function($value){
   if(!is_string($value)) return $value;
   // Do not allow spreadsheet formula injection from user-controlled names/notes.
   return preg_match('/^\s*[=+\-@]/u',$value) ? "'".$value : $value;
  },$row),$this->rows);
 }
}