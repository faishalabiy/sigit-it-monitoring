<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
class RoleManagementController extends Controller {
 public function index(){return view('admin.roles.index',['roles'=>Role::withCount('users')->orderBy('name')->get()]);}
 public function create(){return view('admin.roles.form',['role'=>new Role,'permissions'=>Role::PERMISSIONS]);}
 public function store(Request $request){
  $data=$this->validated($request);$data['slug']=Str::slug($data['name']);
  $request->validate(['name'=>[Rule::unique('roles','name')]]);if(Role::where('slug',$data['slug'])->exists())return back()->withErrors(['name'=>'Nama role sudah digunakan.']);
  Role::create($data);return redirect()->route('admin.roles.index')->with('success','Role berhasil dibuat.');
 }
 public function edit(Role $role){return view('admin.roles.form',compact('role')+['permissions'=>Role::PERMISSIONS]);}
 public function update(Request $request,Role $role){
  $data=$this->validated($request);
  $request->validate(['name'=>[Rule::unique('roles','name')->ignore($role->id)]]);
  if($role->slug==='super-admin' && (count(array_diff(array_keys(Role::PERMISSIONS),$data['permissions']))>0))return back()->withErrors(['permissions'=>'Hak akses Super Admin tidak dapat dikurangi.']);
  if(in_array('users.manage',$role->permissions??[],true) && !in_array('users.manage',$data['permissions'],true)
    && $role->users()->exists() && !Role::where('id','!=',$role->id)->get()->contains(fn($r)=>in_array('users.manage',$r->permissions??[],true) && $r->users()->exists())
    && empty(array_filter(array_map('trim',explode(',',(string)config('iams.admin_user_ids',''))))))
    return back()->withErrors(['permissions'=>'Tidak boleh menghapus pengelola akun terakhir.']);
  $role->update($data);return redirect()->route('admin.roles.index')->with('success','Role diperbarui.');
 }
 public function destroy(Role $role){
  if($role->slug==='super-admin'||$role->users()->exists())return back()->with('error','Role Super Admin atau role yang masih digunakan tidak dapat dihapus.');
  $role->delete();return back()->with('success','Role dihapus.');
 }
 private function validated(Request $request):array {
  $data=$request->validate(['name'=>'required|string|max:100','description'=>'nullable|string|max:500','permissions'=>'required|array|min:1','permissions.*'=>['string',Rule::in(array_keys(Role::PERMISSIONS))]]);
  $data['permissions']=array_values(array_unique($data['permissions']));return $data;
 }
}