<?php

namespace Tests\Feature;

use App\Models\PmParameterTemplate;
use App\Models\PmSystem;
use App\Services\PmProbeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SigitPmAppHealthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('pm.local_hosts',['192.168.13.191']);
        config()->set('pm.health_clients',[
            'sipo.ptrekaindo.co.id'=>str_repeat('s',64),
            'paduka.ptrekaindo.co.id'=>str_repeat('p',64),
        ]);
    }

    private function parameter(string $method): PmParameterTemplate
    {
        return PmParameterTemplate::create([
            'code'=>strtoupper($method),'name'=>$method,'method'=>$method,
            'category'=>'availability','is_active'=>true,
            'expected_value'=>$method==='database_connection'?'connected':'running',
        ]);
    }

    private function system(string $name,string $host='192.168.13.191'): PmSystem
    {
        return PmSystem::create([
            'code'=>strtoupper($name),'name'=>$name,'type'=>'application',
            'status'=>'active','environment'=>'production','criticality'=>'high',
            'host'=>$host,
            'target_url'=>'https://'.strtolower($name).'.ptrekaindo.co.id',
        ]);
    }

    public function test_signed_health_check_reads_independent_database_status_for_each_application(): void
    {
        $db=$this->parameter('database_connection');
        $service=$this->parameter('service_status');
        $sipo=$this->system('SIPO');
        $paduka=$this->system('PADUKA');
        Http::fake([
            'sipo.ptrekaindo.co.id/internal/pm/health'=>Http::response([
                'database'=>'connected','service'=>'running',
            ],200),
            'paduka.ptrekaindo.co.id/internal/pm/health'=>Http::response([
                'database'=>'disconnected','service'=>'running',
            ],200),
        ]);

        $probe=app(PmProbeService::class);
        $this->assertSame('pass',$probe->check($sipo,$db)['status']);
        $this->assertSame('pass',$probe->check($sipo,$service)['status']);
        $this->assertSame('fail',$probe->check($paduka,$db)['status']);
        $this->assertSame('pass',$probe->check($paduka,$service)['status']);

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) =>
            $request->hasHeader('X-PM-Timestamp')
            && $request->hasHeader('X-PM-Signature')
            && str_ends_with($request->url(),'/internal/pm/health')
        );
    }

    public function test_untrusted_host_or_failed_health_auth_never_generates_false_pass(): void
    {
        $db=$this->parameter('database_connection');
        $bad=$this->system('SIPO','192.168.13.192');
        $response=app(PmProbeService::class)->check($bad,$db);
        $this->assertSame('unavailable',$response['status']);

        $bad->update(['host'=>'192.168.13.191']);
        Http::fake(['sipo.ptrekaindo.co.id/internal/pm/health'=>Http::response(
            ['message'=>'Unauthorized'],401
        )]);
        $this->assertSame('unavailable',app(PmProbeService::class)->check($bad,$db)['status']);
    }

    public function test_web_http_status_uses_explicit_health_path_not_login_redirect(): void
    {
        $system=$this->system('PADUKA');
        $parameter=PmParameterTemplate::create([
            'code'=>'HTTP_ROOT','name'=>'HTTP','method'=>'http_status',
            'category'=>'availability','expected_value'=>'200','is_active'=>true,
        ]);
        Http::fake([
            'paduka.ptrekaindo.co.id/up'=>Http::response('ok',200),
            'paduka.ptrekaindo.co.id/'=>Http::response('',302),
        ]);
        $result=app(PmProbeService::class)->check($system,$parameter);
        $this->assertSame('pass',$result['status']);
        $this->assertSame('200',$result['value']);
        $this->assertStringContainsString('Endpoint pemeriksaan: /up',$result['message']);
        Http::assertSentCount(1);
        Http::assertSent(fn($request)=>$request->url()==='https://paduka.ptrekaindo.co.id/up');
    }

    public function test_malformed_health_response_is_unavailable_not_healthy(): void
    {
        $db=$this->parameter('database_connection');
        $app=$this->system('SIPO');
        Http::fake(['sipo.ptrekaindo.co.id/internal/pm/health'=>Http::response(
            ['service'=>'running','database'=>'unknown'],200
        )]);
        $this->assertSame('unavailable',app(PmProbeService::class)->check($app,$db)['status']);
    }
}
