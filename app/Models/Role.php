<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
class Role extends Model {
 protected $fillable = ['name','slug','description','permissions'];
 protected function casts(): array {return ['permissions'=>'array'];}
 public function users(): BelongsToMany {return $this->belongsToMany(User::class);}
 public const PERMISSIONS = [
 'assets.view'=>'Lihat aset','assets.manage'=>'Kelola aset',
 'borrowings.view'=>'Lihat peminjaman','borrowings.manage'=>'Proses peminjaman',
 'reports.view'=>'Lihat dan ekspor laporan','users.manage'=>'Kelola pengguna','roles.manage'=>'Kelola role',
 'pm.view'=>'Lihat konfigurasi preventive maintenance',
 'pm.manage'=>'Kelola master sistem, template parameter, dan jadwal PM',
 'pm.approve'=>'Review, setujui, tolak, dan finalisasi laporan PM',
 ];
}