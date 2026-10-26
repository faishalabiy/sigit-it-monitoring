<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class SigitSecurityTest extends TestCase {
 use RefreshDatabase;
 public function test_public_registration_is_disabled(): void {
  $this->get('/register')->assertNotFound();
  $this->post('/register', ['name'=>'test','email'=>'test@example.test','password'=>'Password123!','password_confirmation'=>'Password123!'])->assertNotFound();
  $this->assertDatabaseCount('users', 0);
 }
 public function test_unapproved_authenticated_user_cannot_access_admin(): void {
  config()->set('iams.admin_user_ids','1,2');
  $user=User::factory()->create(['id'=>999]);
  $this->actingAs($user)->get('/admin/aset')->assertForbidden();
  $this->actingAs($user)->get('/admin/peminjaman')->assertForbidden();
  $this->actingAs($user)->post('/admin/aset',[])->assertForbidden();
 }
 public function test_guest_cannot_access_admin(): void {
  $this->get('/admin/aset')->assertRedirect('/login');
 }
}
