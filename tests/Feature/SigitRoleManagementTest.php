<?php
namespace Tests\Feature;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SigitRoleManagementTest extends TestCase {
 use RefreshDatabase;
 public function test_role_can_be_created_and_assigned_to_new_user():void {
  config()->set('iams.admin_user_ids','1');
  $admin=User::factory()->create(['id'=>1]);
  $this->actingAs($admin)->post(route('admin.roles.store'),[
   'name'=>'Auditor','permissions'=>['assets.view','reports.view']
  ])->assertRedirect(route('admin.roles.index'));
  $role=Role::where('slug','auditor')->firstOrFail();
  $this->actingAs($admin)->post(route('admin.users.store'),[
   'name'=>'Penguji','email'=>'penguji@example.test','password'=>'Passw0rd!strong',
   'password_confirmation'=>'Passw0rd!strong','roles'=>[$role->id]
  ])->assertRedirect(route('admin.users.index'));
  $user=User::where('email','penguji@example.test')->firstOrFail();
  $this->assertTrue($user->hasPermission('assets.view'));
  $this->assertFalse($user->hasPermission('assets.manage'));
  $this->actingAs($user)->get(route('admin.assets.index'))->assertOk();
  $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
  $this->actingAs($user)->post(route('admin.assets.store'),[])->assertForbidden();
 }
 public function test_user_without_role_is_forbidden():void {
  config()->set('iams.admin_user_ids','1');
  $user=User::factory()->create(['id'=>999]);
  $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
 }
 public function test_admin_cannot_delete_own_account():void {
  config()->set('iams.admin_user_ids','1');
  $admin=User::factory()->create(['id'=>1]);
  $this->actingAs($admin)->delete(route('admin.users.destroy',$admin))->assertRedirect();
  $this->assertNotNull($admin->fresh());
 }
}