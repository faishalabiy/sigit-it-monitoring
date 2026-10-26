<?php
namespace Database\Seeders;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
class SigitRolesSeeder extends Seeder {
 public function run(): void {
  $roles=[
   ['name'=>'Super Admin','slug'=>'super-admin','permissions'=>array_keys(Role::PERMISSIONS),'description'=>'Akses penuh sistem'],
   ['name'=>'Admin Aset','slug'=>'admin-aset','permissions'=>['assets.view','assets.manage','borrowings.view','borrowings.manage','reports.view'],'description'=>'Mengelola aset dan peminjaman'],
   ['name'=>'Petugas','slug'=>'petugas','permissions'=>['assets.view','borrowings.view','borrowings.manage'],'description'=>'Operasional peminjaman'],
   ['name'=>'Viewer','slug'=>'viewer','permissions'=>['assets.view','borrowings.view','reports.view'],'description'=>'Hanya membaca data'],
  ];
  foreach($roles as $data){Role::firstOrCreate(['slug'=>$data['slug']],$data);}
  $admin=Role::where('slug','super-admin')->firstOrFail();
  foreach(array_filter(array_map('trim',explode(',',(string)config('iams.admin_user_ids','')))) as $id){
   $user=User::find($id);if($user){$user->roles()->syncWithoutDetaching([$admin->id]);}
  }
 }
}