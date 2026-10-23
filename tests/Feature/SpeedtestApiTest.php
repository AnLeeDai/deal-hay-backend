<?php

use App\Models\User;
use App\Services\SpeedtestSession;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

function speedtestGrant(): array
{
    return app(SpeedtestSession::class)->issue('test-speedtest-user');
}

it('issues scoped short lived speedtest grants only to authenticated active users and throttles starts', function (): void {
    $this->postJson('/api/diagnostics/speedtest/session')->assertUnauthorized();
    Sanctum::actingAs(User::factory()->customer()->create(['is_active' => false]));
    $this->postJson('/api/diagnostics/speedtest/session')->assertForbidden();
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);
    $this->freezeTime();
    $response = $this->postJson('/api/diagnostics/speedtest/session')->assertOk()
        ->assertJsonPath('endpoint_path', '/diagnostics/speedtest')
        ->assertJsonPath('expires_at', now()->addSeconds(120)->toIso8601String())
        ->assertJsonPath('limits.max_download_bytes', 4194304)
        ->assertJsonPath('limits.max_upload_bytes', 2097152)
        ->assertJsonPath('limits.max_session_bytes', 67108864)
        ->assertJsonPath('limits.max_requests', 64);
    expect($response->json('token'))->toMatch('/^[a-zA-Z0-9]{64}$/');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $this->postJson('/api/diagnostics/speedtest/session')->assertOk();
    $this->postJson('/api/diagnostics/speedtest/session')->assertOk();
    $this->postJson('/api/diagnostics/speedtest/session')->assertTooManyRequests();
});

it('rejects missing expired and unrelated bearer tokens on every probe', function (): void {
    $token = speedtestGrant()['token'];
    $this->freezeTime();
    foreach ([null, 'not-a-speedtest-token', str_repeat('a', 64)] as $invalid) {
        $headers = $invalid ? ['Authorization' => 'Bearer '.$invalid] : [];
        $this->getJson('/api/diagnostics/speedtest/ping', $headers)->assertUnauthorized();
        $this->getJson('/api/diagnostics/speedtest/download?bytes=32', $headers)->assertUnauthorized();
        $this->call('POST', '/api/diagnostics/speedtest/upload', server: [
            'HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => $invalid ? 'Bearer '.$invalid : '',
            'CONTENT_TYPE' => 'application/octet-stream', 'CONTENT_LENGTH' => 3,
        ], content: 'abc')->assertUnauthorized();
    }
    $this->withToken($token)->getJson('/api/diagnostics/speedtest/ping')->assertNoContent();
    $this->travel(121)->seconds();
    $this->withToken($token)->getJson('/api/diagnostics/speedtest/ping')->assertUnauthorized();
});

it('keeps the speedtest token separate from normal account access', function (): void {
    $user = User::factory()->customer()->create();
    $accountToken = $user->createToken('normal-account-token')->plainTextToken;
    $this->withToken($accountToken)->getJson('/api/diagnostics/speedtest/ping')->assertUnauthorized();
    $this->withToken(speedtestGrant()['token'])->getJson('/api/auth/me')->assertUnauthorized();
});

it('streams exact incompressible download bytes without caching or database and storage probes', function (): void {
    $token = speedtestGrant()['token'];
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    Storage::shouldReceive('disk')->never();
    $this->withToken($token)->getJson('/api/diagnostics/speedtest/ping')->assertNoContent()
        ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, no-transform, private')
        ->assertHeader('Pragma', 'no-cache')->assertHeader('Expires', '0');
    $response = $this->withToken($token)->get('/api/diagnostics/speedtest/download?bytes=65536')
        ->assertOk()->assertHeader('Content-Type', 'application/octet-stream')->assertHeader('Content-Length', '65536')
        ->assertHeader('Content-Encoding', 'identity')->assertHeader('X-Speedtest-Bytes', '65536');
    $content = $response->streamedContent();
    expect(strlen($content))->toBe(65536);
    expect(strlen(gzencode($content, 9)))->toBeGreaterThan(65000);
    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('no-transform');
    $other = $this->withToken($token)->get('/api/diagnostics/speedtest/download?bytes=65536')->streamedContent();
    expect(hash('sha256', $other))->not->toBe(hash('sha256', $content));
    expect($queries)->toBeEmpty();
});

it('rejects download sizes outside the bounded range', function (string $bytes): void {
    $this->withToken(speedtestGrant()['token'])->getJson('/api/diagnostics/speedtest/download?bytes='.$bytes)->assertUnprocessable();
})->with(['0', '-1', '4194305', '1.5', 'not-a-size']);

it('prefers bounded download-size headers so adaptive samples can reuse one preflight URL', function (): void {
    $this->withToken(speedtestGrant()['token']);
    $response = $this->get('/api/diagnostics/speedtest/download?bytes=7', ['X-Speedtest-Bytes' => '12345'])
        ->assertOk()->assertHeader('Content-Length', '12345')->assertHeader('X-Speedtest-Bytes', '12345');
    expect(strlen($response->streamedContent()))->toBe(12345);
    expect($response->headers->get('Vary'))->toContain('X-Speedtest-Bytes');
    $response = $this->get('/api/diagnostics/speedtest/download', ['x-speedtest-bytes' => '256'])
        ->assertOk()->assertHeader('Content-Length', '256');
    expect(strlen($response->streamedContent()))->toBe(256);
    foreach (['0', '-1', '4194305', '1.5', 'invalid'] as $invalid) {
        $this->getJson('/api/diagnostics/speedtest/download?bytes=32', ['X-Speedtest-Bytes' => $invalid])->assertUnprocessable();
    }
});

it('counts raw upload bytes and rejects oversized or unsupported bodies without storing payloads', function (): void {
    $token = speedtestGrant()['token'];
    $content = random_bytes(128 * 1024);
    $server = ['HTTP_AUTHORIZATION' => 'Bearer '.$token, 'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/octet-stream', 'CONTENT_LENGTH' => strlen($content)];
    $queries = [];
    DB::listen(function (QueryExecuted $query) use (&$queries): void {
        $queries[] = $query->sql;
    });
    Storage::shouldReceive('disk')->never();
    $this->call('POST', '/api/diagnostics/speedtest/upload', server: $server, content: $content)
        ->assertOk()->assertExactJson(['received_bytes' => strlen($content)])->assertHeader('X-Speedtest-Bytes', (string) strlen($content))
        ->assertHeader('Cache-Control', 'max-age=0, must-revalidate, no-cache, no-store, no-transform, private')
        ->assertHeader('Pragma', 'no-cache')->assertHeader('Expires', '0');
    $this->call('POST', '/api/diagnostics/speedtest/upload', server: [...$server, 'CONTENT_LENGTH' => SpeedtestSession::MAX_UPLOAD_BYTES + 1], content: 'x')->assertStatus(413);
    $this->call('POST', '/api/diagnostics/speedtest/upload', server: [...$server, 'CONTENT_LENGTH' => 3, 'CONTENT_TYPE' => 'text/plain'], content: 'abc')->assertStatus(415);
    $this->call('POST', '/api/diagnostics/speedtest/upload', server: [...$server, 'CONTENT_LENGTH' => 1], content: 'abc')->assertBadRequest();
    expect($queries)->toBeEmpty();
});

it('bounds streamed uploads even without a declared Content Length', function (): void {
    $server = ['HTTP_AUTHORIZATION' => 'Bearer '.speedtestGrant()['token'], 'HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/octet-stream'];
    $this->call('POST', '/api/diagnostics/speedtest/upload', server: $server, content: str_repeat('x', SpeedtestSession::MAX_UPLOAD_BYTES))
        ->assertOk()->assertJsonPath('received_bytes', SpeedtestSession::MAX_UPLOAD_BYTES);
    $this->call('POST', '/api/diagnostics/speedtest/upload', server: $server, content: str_repeat('x', SpeedtestSession::MAX_UPLOAD_BYTES + 1))
        ->assertStatus(413);
});

it('enforces session byte and request budgets independently between grants', function (): void {
    $service = app(SpeedtestSession::class);
    $token = speedtestGrant()['token'];
    for ($index = 0; $index < 16; $index++) {
        $service->consume($token, SpeedtestSession::MAX_DOWNLOAD_BYTES);
    }
    $this->withToken($token)->getJson('/api/diagnostics/speedtest/download?bytes=1')->assertTooManyRequests();
    $second = speedtestGrant()['token'];
    for ($index = 0; $index < SpeedtestSession::MAX_REQUESTS; $index++) {
        $service->consume($second);
    }
    $this->withToken($second)->getJson('/api/diagnostics/speedtest/ping')->assertTooManyRequests();
    $this->withToken(speedtestGrant()['token'])->getJson('/api/diagnostics/speedtest/ping')->assertNoContent();
});

it('does not let an understated Content Length bypass the session transfer budget', function (): void {
    $service = app(SpeedtestSession::class);
    $token = speedtestGrant()['token'];
    for ($index = 0; $index < 31; $index++) {
        $service->consume($token, SpeedtestSession::MAX_UPLOAD_BYTES);
    }
    $this->call('POST', '/api/diagnostics/speedtest/upload', server: [
        'HTTP_AUTHORIZATION' => 'Bearer '.$token, 'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/octet-stream', 'CONTENT_LENGTH' => 1,
    ], content: 'abc')->assertBadRequest();
    $this->withToken($token)->getJson('/api/diagnostics/speedtest/download?bytes=1')->assertTooManyRequests();
});

it('supports authenticated cross origin probes and preflights from the configured frontend', function (): void {
    config()->set('cors.allowed_origins', ['https://frontend.example.test']);
    $this->options('/api/diagnostics/speedtest/upload', headers: [
        'Origin' => 'https://frontend.example.test', 'Access-Control-Request-Method' => 'POST',
        'Access-Control-Request-Headers' => 'authorization,content-type',
    ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'https://frontend.example.test');
    $this->options('/api/diagnostics/speedtest/download', headers: [
        'Origin' => 'https://frontend.example.test', 'Access-Control-Request-Method' => 'GET',
        'Access-Control-Request-Headers' => 'authorization,x-speedtest-bytes',
    ])->assertNoContent()->assertHeader('Access-Control-Allow-Origin', 'https://frontend.example.test')
        ->assertHeader('Access-Control-Allow-Headers', 'authorization,x-speedtest-bytes');
    $this->withToken(speedtestGrant()['token'])->get('/api/diagnostics/speedtest/download?bytes=1', ['Origin' => 'https://frontend.example.test'])
        ->assertOk()->assertHeader('Access-Control-Allow-Origin', 'https://frontend.example.test')
        ->assertHeader('Timing-Allow-Origin', 'https://frontend.example.test');
    $this->assertTrue(in_array('X-Speedtest-Bytes', config('cors.exposed_headers'), true));
    $this->assertTrue(in_array('Content-Encoding', config('cors.exposed_headers'), true));
});
