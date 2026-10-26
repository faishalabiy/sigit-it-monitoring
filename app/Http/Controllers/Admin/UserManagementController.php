<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
class UserManagementController extends Controller {
 public function index(Request $request) {
  $users=User::with('roles')->when($request->search,fn($q)=>$q->where(fn($s)=>$s->where('name','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%')))->orderBy('name')->paginate(15)->withQueryString();
  return view('admin.users.index',compact('users'));
 }
 public function create(){return view('admin.users.form',['user'=>new User,'roles'=>Role::orderBy('name')->get()]);}
 public function store(Request $request){
  $data=$request->validate(['name'=>'required|string|max:255','email'=>'required|email|max:255|unique:users,email','password'=>['required','confirmed',Password::min(8)->mixedCase()->numbers()->symbols()],'roles'=>'required|array|min:1','roles.*'=>'integer|exists:roles,id']);
  DB::transaction(function()use($data){$user=User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>$data['password']]);$user->roles()->sync($data['roles']);});
  return redirect()->route('admin.users.index')->with('success','Pengguna berhasil ditambahkan.');
 }
 public function edit(User $user){return view('admin.users.form',['user'=>$user,'roles'=>Role::orderBy('name')->get()]);}
 public function update(Request $request,User $user){
  $data=$request->validate(['name'=>'required|string|max:255','email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user->id)],'password'=>['nullable','confirmed',Password::min(8)->mixedCase()->numbers()->symbols()],'roles'=>'required|array|min:1','roles.*'=>'integer|exists:roles,id']);
  $newRoles=Role::whereIn('id',$data['roles'])->get();
  if($user->id===$request->user()->id && !$newRoles->contains(fn($r)=>in_array('users.manage',$r->permissions??[])) && !$request->user()->isLegacyAdmin())return back()->withErrors(['roles'=>'Tidak boleh mencabut hak pengelolaan akun sendiri.']);
  if($user->hasPermission('users.manage') && !$newRoles->contains(fn($r)=>in_array('users.manage',$r->permissions??[])) && !$this->otherManagersExist($user))return back()->withErrors(['roles'=>'Tidak boleh mencabut pengelola terakhir.']);
  DB::transaction(function()use($data,$user){$values=['name'=>$data['name'],'email'=>$data['email']];if(!empty($data['password']))$values['password']=$data['password'];$user->update($values);$user->roles()->sync($data['roles']);});
  return redirect()->route('admin.users.index')->with('success','Pengguna berhasil diperbarui.');
 }
 public function destroy(Request $request,User $user){
  if($user->id===$request->user()->id)return back()->with('error','Tidak dapat menghapus akun sendiri.');
  if($user->hasPermission('users.manage') && !$this->otherManagersExist($user))return back()->with('error','Tidak dapat menghapus pengelola terakhir.');
  if($user->borrowingsProcessed()->exists())return back()->with('error','Pengguna memiliki riwayat peminjaman. Ubah role atau nonaktifkan akses, jangan hapus.');
  $user->delete();return redirect()->route('admin.users.index')->with('success','Pengguna dihapus.');
 }
 private function otherManagersExist(User $user):bool {
  $ids=Role::all()->filter(fn($role)=>in_array('users.manage',$role->permissions??[]))->pluck('id');
  return User::where('id','!=',$user->id)->whereHas('roles',fn($q)=>$q->whereIn('roles.id',$ids))->exists()
    || User::where('id','!=',$user->id)->whereIn('id',array_filter(array_map('trim',explode(',',(string)config('iams.admin_user_ids','')))))->exists();
 }
}