#!/usr/bin/env php
<?php

/**
 * Real FrankenPHP integration checks and an optional local classic/Octane benchmark.
 *
 * php scripts/verify-persistent-worker.php --frankenphp=/path/to/frankenphp --requests=100 --measure-queues
 *
 * Everything writable, including SQLite, config caches, public files and probe routes,
 * lives in a temporary app. No application .env, database or deployment is modified.
 * Results describe this machine; they are not a simulation of a 0.5 CPU Render plan.
 */

declare(strict_types=1);

use Symfony\Component\Process\Process;

require dirname(__DIR__).'/vendor/autoload.php';

$options = getopt('', ['frankenphp:', 'requests:', 'classic-config:', 'measure-queues', 'keep']);
$binary = (string) ($options['frankenphp'] ?? getenv('FRANKENPHP_BINARY') ?: 'frankenphp');
$samples = filter_var($options['requests'] ?? 100, FILTER_VALIDATE_INT, ['options' => ['min_range' => 10, 'max_range' => 2000]]);

if ($samples === false) {
    fwrite(STDERR, "--requests must be between 10 and 2000.\n");
    exit(1);
}

$source = dirname(__DIR__);
$temporary = sys_get_temp_dir().'/deal-hay-worker-'.bin2hex(random_bytes(8));
$processes = [];
$exitCode = 0;

try {
    $version = new Process([$binary, 'version']);
    $version->mustRun();
    createFixture($source, $temporary);
    $port = availablePort();
    $environment = [...fixtureEnvironment($temporary, $port), 'PHPRC' => $source.'/php.production.ini'];

    foreach ([['migrate', '--force', '--no-interaction'], ['config:cache'], ['route:cache']] as $arguments) {
        $command = new Process([PHP_BINARY, $temporary.'/artisan', ...$arguments], $temporary, $environment);
        $command->setTimeout(120)->mustRun();
    }

    $seed = new Process([PHP_BINARY, $temporary.'/seed.php'], $temporary, $environment);
    $seed->mustRun();
    $users = json_decode($seed->getOutput(), true, flags: JSON_THROW_ON_ERROR);
    $http = curl_init();
    $worker = startServer($binary, $source.'/Caddyfile', $temporary, [...$environment, 'MAX_REQUESTS' => '12']);
    $processes[] = $worker;
    waitForServer($worker, $http, $port);

    $firstBoot = request($http, $port, '/__worker-probe')['json']['boot_id'];
    assertResponse(request($http, $port, '/api/private/profile/me', $users['a']['token']), 200, $users['a']['id']);
    assertResponse(request($http, $port, '/api/private/profile/me'), 401);
    assertResponse(request($http, $port, '/api/private/profile/me', $users['b']['token']), 200, $users['b']['id']);

    request($http, $port, '/__worker-probe?remember=private-context');
    $after = request($http, $port, '/__worker-probe');
    $runtime = $after['json']['runtime'];
    check($after['json']['boot_id'] === $firstBoot, 'The same application boot must handle several HTTP requests.');
    check($after['json']['previous_context'] === null, 'Context leaked between HTTP requests.');

    $database = new PDO('sqlite:'.$temporary.'/database.sqlite');
    $update = $database->prepare('update users set full_name = ? where id = ?');
    $update->execute(['Changed while worker is running', $users['b']['id']]);
    $fresh = request($http, $port, '/api/private/profile/me', $users['b']['token']);
    assertResponse($fresh, 200, $users['b']['id']);
    check($fresh['json']['auth']['user']['full_name'] === 'Changed while worker is running', 'The worker returned stale user data after a database write.');

    $avatarPaths = [];
    foreach (['a', 'b'] as $name) {
        $avatar = uploadAvatar($http, $port, $source, $temporary, $database, $users[$name]);
        $avatarPaths[$name] = $avatar;
    }
    check($avatarPaths['a'] !== $avatarPaths['b'], 'Different users must receive different avatar object keys.');
    foreach (['a', 'b'] as $name) {
        $profile = request($http, $port, '/api/private/profile/me', $users[$name]['token']);
        assertResponse($profile, 200, $users[$name]['id']);
        check($profile['json']['auth']['user']['avatar_url'] === '/api/media/assets/'.$avatarPaths[$name], 'A multipart upload changed another user avatar.');
    }

    $database->prepare('delete from personal_access_tokens where tokenable_id = ?')->execute([$users['b']['id']]);
    assertResponse(request($http, $port, '/api/private/profile/me', $users['b']['token']), 401);
    $recycled = false;

    for ($attempt = 0; $attempt < 25; $attempt++) {
        if (request($http, $port, '/__worker-probe')['json']['boot_id'] !== $firstBoot) {
            $recycled = true;
            break;
        }
    }

    check($recycled, 'MAX_REQUESTS did not recycle the persistent application.');
    assertResponse(request($http, $port, '/api/private/profile/me', $users['a']['token']), 200, $users['a']['id']);
    uploadAvatar($http, $port, $source, $temporary, $database, $users['a']);
    assertResponse(request($http, $port, '/api/private/profile/me'), 401);
    stopServer($worker);

    $classicPath = $temporary.'/Caddyfile.classic';
    if (isset($options['classic-config'])) {
        $classic = file_get_contents((string) $options['classic-config']);
    } else {
        $classic = <<<'CADDY'
{
    auto_https off
    admin off
    frankenphp {
        num_threads {$FRANKENPHP_NUM_THREADS:1}
        max_threads {$FRANKENPHP_MAX_THREADS:2}
    }
}
:{$PORT:10000} {
    root * "{$APP_PUBLIC_PATH:/app/public}"
    encode {
        gzip 1
        minimum_length 1024
    }
    log {
        output stdout
        format json
    }
    php_server
}
CADDY;
    }

    check(is_string($classic) && ! str_contains($classic, 'frankenphp-worker.php'), 'The classic baseline must be a pre-Octane Caddyfile; pass --classic-config=/path/to/Caddyfile.');
    file_put_contents($classicPath, str_replace('/app/public', $temporary.'/public', $classic));
    $measurements = [];

    foreach (['classic' => $classicPath, 'persistent' => $source.'/Caddyfile'] as $mode => $configuration) {
        $http = curl_init();
        $server = startServer($binary, $configuration, $temporary, [...$environment, 'MAX_REQUESTS' => '500']);
        $processes[] = $server;
        waitForServer($server, $http, $port);

        for ($warmup = 0; $warmup < 10; $warmup++) {
            $server->getIncrementalOutput();
            $server->getIncrementalErrorOutput();
            assertResponse(request($http, $port, '/api/private/profile/me', $users['a']['token']), 200, $users['a']['id']);
        }

        $times = [];
        for ($sample = 0; $sample < $samples; $sample++) {
            $server->getIncrementalOutput();
            $server->getIncrementalErrorOutput();
            $response = request($http, $port, '/api/private/profile/me', $users['a']['token']);
            assertResponse($response, 200, $users['a']['id']);
            $times[] = $response['milliseconds'];
        }

        sort($times);
        $measurements[$mode] = [
            'requests' => $samples,
            'median_ms' => round(percentile($times, 0.5), 3),
            'p95_ms' => round(percentile($times, 0.95), 3),
            'process_rss_mib' => residentMemory($server->getPid()),
        ];
        if ($mode === 'persistent' && isset($options['measure-queues'])) {
            $queues = [];
            foreach (['realtime' => '0.25', 'images,default' => '3'] as $name => $sleep) {
                $queue = new Process([
                    PHP_BINARY, '-d', 'memory_limit=128M', $temporary.'/artisan',
                    'queue:work', 'database_outbox', '--queue='.$name, '--sleep='.$sleep,
                    '--tries=5', '--timeout=60', '--memory=96', '--max-jobs=500', '--max-time=3600', '--no-interaction',
                ], $temporary, $environment);
                $queue->setTimeout(null)->start();
                $queues[$name] = $queue;
                $processes[] = $queue;
            }
            usleep(750_000);
            $queueMemory = [];
            foreach ($queues as $name => $queue) {
                check($queue->isRunning(), 'Idle queue process exited: '.$queue->getErrorOutput());
                $queueMemory[$name] = residentMemory($queue->getPid());
                stopServer($queue);
            }
            $measurements[$mode]['idle_queue_process_rss_mib'] = $queueMemory;
            $measurements[$mode]['idle_http_plus_queues_rss_sum_mib'] = round($measurements[$mode]['process_rss_mib'] + array_sum($queueMemory), 1);
            $measurements[$mode]['queue_php_binary'] = PHP_BINARY;
        }
        stopServer($server);
    }

    echo json_encode([
        'runtime' => trim($version->getOutput()),
        'php' => $runtime,
        'checks' => ['boot_reused', 'user_a_guest_user_b_isolated', 'context_reset', 'live_database_updates', 'multipart_upload_bytes_preserved', 'multipart_user_ownership', 'token_revocation', 'worker_recycling', 'multipart_upload_after_recycling'],
        'benchmark' => [
            'endpoint' => '/api/private/profile/me',
            'database' => 'isolated local SQLite',
            'concurrency' => 1,
            'warmup_requests_per_mode' => 10,
            'measurements' => $measurements,
            'median_speedup' => round($measurements['classic']['median_ms'] / $measurements['persistent']['median_ms'], 2),
            'scope' => 'Local loopback HTTP on this machine, without a 0.5 CPU quota. RSS includes shared pages; queue sums double-count shared pages and exclude OS, Supervisor and scheduler/job peaks. Local PHP/extensions can differ from Docker.',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    foreach ($processes as $process) {
        $error = trim($process->getErrorOutput());
        if ($error !== '') {
            fwrite(STDERR, substr($error, -8000).PHP_EOL);
        }
    }
    $exitCode = 1;
} finally {
    foreach ($processes as $process) {
        stopServer($process);
    }
    if (isset($options['keep'])) {
        fwrite(STDERR, 'Fixture kept at '.$temporary.PHP_EOL);
    } else {
        removeFixture($temporary);
    }
}

exit($exitCode);

function createFixture(string $source, string $temporary): void
{
    foreach (['bootstrap/cache', 'public', 'storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $directory) {
        mkdir($temporary.'/'.$directory, 0755, true);
    }
    foreach (['app', 'config', 'database', 'resources', 'routes', 'vendor', 'composer.json', 'composer.lock'] as $path) {
        symlink($source.'/'.$path, $temporary.'/'.$path);
    }
    foreach (['artisan', 'bootstrap/app.php', 'public/index.php', 'public/frankenphp-worker.php'] as $path) {
        copy($source.'/'.$path, $temporary.'/'.$path);
    }
    $bootstrap = file_get_contents($temporary.'/bootstrap/app.php');
    file_put_contents($temporary.'/bootstrap/app.php', preg_replace('/<\?php/', "<?php\nrequire_once __DIR__.'/PersistentWorkerProbeProvider.php';", $bootstrap, 1));
    touch($temporary.'/database.sqlite');
    $providers = require $source.'/bootstrap/providers.php';
    $providers[] = 'DealHayPersistentWorkerProbeProvider';
    file_put_contents($temporary.'/bootstrap/providers.php', "<?php\nrequire_once __DIR__.'/PersistentWorkerProbeProvider.php';\nreturn ".var_export($providers, true).";\n");
    file_put_contents($temporary.'/bootstrap/PersistentWorkerProbeProvider.php', <<<'PHP'
<?php

class DealHayPersistentWorkerProbeProvider extends \Illuminate\Support\ServiceProvider
{
    public function register(): void
    {
        $this->app->instance('integration.worker_boot_id', bin2hex(random_bytes(16)));
    }

    public function boot(): void
    {
        \Illuminate\Support\Facades\Route::get('/__worker-probe', static function () {
            $previous = \Illuminate\Support\Facades\Context::getHidden('integration.secret');
            if (request()->has('remember')) {
                \Illuminate\Support\Facades\Context::addHidden('integration.secret', request()->query('remember'));
            }
            return response()->json([
                'boot_id' => app('integration.worker_boot_id'),
                'previous_context' => $previous,
                'runtime' => [
                    'sapi' => PHP_SAPI,
                    'opcache_enabled' => (bool) (opcache_get_status(false)['opcache_enabled'] ?? false),
                    'memory_limit' => ini_get('memory_limit'),
                    'heap_used_mib' => round(memory_get_usage(true) / 1048576, 1),
                ],
            ]);
        });
    }
}
PHP);
    file_put_contents($temporary.'/seed.php', <<<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$users = [];
foreach (['a', 'b'] as $index => $name) {
    $user = \App\Models\User::create([
        'role' => 'customer', 'user_code' => 'CUS-PROBE-'.$name,
        'full_name' => 'Worker Probe '.$name, 'email' => $name.'@worker-probe.invalid',
        'password' => password_hash('probe-test-password', PASSWORD_BCRYPT, ['cost' => 4]),
        'is_active' => true,
    ]);
    $users[$name] = ['id' => $user->id, 'user_code' => $user->user_code, 'token' => $user->createToken('probe')->plainTextToken];
}
echo json_encode($users, JSON_THROW_ON_ERROR);
PHP);
}

function fixtureEnvironment(string $temporary, int $port): array
{
    return [
        'APP_ENV' => 'production', 'APP_DEBUG' => 'false',
        'APP_BASE_PATH' => $temporary, 'APP_PUBLIC_PATH' => $temporary.'/public',
        'LARAVEL_STORAGE_PATH' => $temporary.'/storage',
        'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
        'APP_URL' => 'http://127.0.0.1:'.$port,
        'APP_CONFIG_CACHE' => $temporary.'/bootstrap/cache/config.php',
        'APP_ROUTES_CACHE' => $temporary.'/bootstrap/cache/routes.php',
        'APP_SERVICES_CACHE' => $temporary.'/bootstrap/cache/services.php',
        'APP_PACKAGES_CACHE' => $temporary.'/bootstrap/cache/packages.php',
        'APP_EVENTS_CACHE' => $temporary.'/bootstrap/cache/events.php',
        'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $temporary.'/database.sqlite', 'DB_URL' => '',
        'CACHE_STORE' => 'file', 'REFERENCE_CACHE_STORE' => 'file', 'CACHE_LIMITER_STORE' => 'file',
        'PRESENCE_CACHE_STORE' => 'file', 'SESSION_REGISTRY_CACHE_STORE' => 'file',
        'SPEEDTEST_CACHE_STORE' => 'file', 'CACHE_PREFIX' => 'worker-probe',
        'QUEUE_CONNECTION' => 'sync', 'BROADCAST_CONNECTION' => 'null',
        'MAIL_MAILER' => 'array', 'SESSION_DRIVER' => 'file',
        'FILESYSTEM_DISK' => 'local', 'R2_DRIVER' => 'local', 'R2_PRIVATE_DRIVER' => 'local', 'CHAT_MEDIA_DISK' => 'local',
        'ADMIN_USER_CODE' => 'AD-PROBE', 'ADMIN_FULL_NAME' => 'Worker Probe Admin',
        'ADMIN_EMAIL' => 'admin@worker-probe.invalid', 'ADMIN_PASSWORD' => 'probe-admin-password',
        'LOG_CHANNEL' => 'stderr', 'LOG_LEVEL' => 'warning', 'BCRYPT_ROUNDS' => '4',
        'LARAVEL_OCTANE' => '1', 'OCTANE_SERVER' => 'frankenphp', 'OCTANE_HTTPS' => 'false',
        'FRANKENPHP_NUM_THREADS' => '1', 'FRANKENPHP_MAX_THREADS' => '2', 'OCTANE_WORKERS' => '1',
        'REQUEST_MAX_EXECUTION_TIME' => '60', 'PORT' => (string) $port,
        'GOMAXPROCS' => '1', 'GOMEMLIMIT' => '64MiB',
        'XDG_CONFIG_HOME' => $temporary.'/caddy/config', 'XDG_DATA_HOME' => $temporary.'/caddy/data',
    ];
}

function startServer(string $binary, string $configuration, string $temporary, array $environment): Process
{
    $process = new Process([$binary, 'run', '--config', $configuration, '--adapter', 'caddyfile'], $temporary, $environment);
    $process->setTimeout(null)->start();

    return $process;
}

function waitForServer(Process $process, CurlHandle $http, int $port): void
{
    $deadline = microtime(true) + 20;
    do {
        check($process->isRunning(), 'FrankenPHP exited before becoming ready: '.$process->getErrorOutput());
        try {
            if (request($http, $port, '/__worker-probe')['status'] === 200) {
                return;
            }
        } catch (RuntimeException) {
        }
        usleep(100_000);
    } while (microtime(true) < $deadline);

    throw new RuntimeException('Timed out waiting for FrankenPHP readiness.');
}

function request(CurlHandle $http, int $port, string $path, ?string $token = null, ?array $multipart = null): array
{
    curl_reset($http);
    curl_setopt_array($http, [
        CURLOPT_URL => 'http://127.0.0.1:'.$port.$path,
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT_MS => 500,
        CURLOPT_TIMEOUT_MS => 5000, CURLOPT_PROXY => '',
        CURLOPT_HTTPHEADER => array_values(array_filter(['Accept: application/json', $token === null ? null : 'Authorization: Bearer '.$token])),
    ]);
    if ($multipart !== null) {
        curl_setopt($http, CURLOPT_POSTFIELDS, $multipart);
    }
    $body = curl_exec($http);
    check($body !== false, 'HTTP request '.$path.' failed: '.curl_error($http));

    return [
        'status' => curl_getinfo($http, CURLINFO_RESPONSE_CODE),
        'milliseconds' => curl_getinfo($http, CURLINFO_TOTAL_TIME) * 1000,
        'json' => json_decode($body, true),
        'body' => $body,
    ];
}

function uploadAvatar(CurlHandle $http, int $port, string $source, string $temporary, PDO $database, array $user): string
{
    $original = $source.'/tests/Fixtures/prepared.webp';
    $response = request($http, $port, '/api/private/me/avatar', $user['token'], [
        'avatar' => new CURLFile($original, 'image/webp', 'prepared.webp'),
    ]);
    assertResponse($response, 200);
    check(($response['json']['data']['id'] ?? null) === $user['id'], 'Multipart upload used another user identity.');
    $query = $database->prepare('select avatar_url from users where id = ?');
    $query->execute([$user['id']]);
    $key = $query->fetchColumn();
    check(is_string($key) && str_starts_with($key, $user['user_code'].'/avatar/') && ! str_contains($key, '..'), 'Multipart upload did not store an avatar key owned by the authenticated user.');
    $saved = $temporary.'/storage/app/r2/'.$key;
    check(is_file($saved), 'Multipart upload did not save the avatar in the isolated local R2 disk.');
    check(hash_file('sha256', $saved) === hash_file('sha256', $original), 'Multipart upload modified the prepared WebP bytes.');

    return $key;
}

function assertResponse(array $response, int $status, ?string $userId = null): void
{
    check($response['status'] === $status, 'Unexpected HTTP status '.$response['status'].' (expected '.$status.'): '.substr($response['body'], 0, 500));
    if ($userId !== null) {
        check(($response['json']['auth']['user']['id'] ?? null) === $userId, 'The response contains the wrong user identity.');
    }
}

function percentile(array $values, float $percentile): float
{
    return $values[max(0, (int) ceil(count($values) * $percentile) - 1)];
}

function availablePort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    check($socket !== false, 'Could not reserve a local port.');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);

    return (int) substr($address, strrpos($address, ':') + 1);
}

function residentMemory(?int $pid): ?float
{
    if ($pid !== null && is_readable('/proc/'.$pid.'/status')) {
        preg_match('/^VmRSS:\s+(\d+)\s+kB/m', file_get_contents('/proc/'.$pid.'/status'), $match);
        if (isset($match[1])) {
            return round((int) $match[1] / 1024, 1);
        }
    }

    return null;
}

function stopServer(Process $process): void
{
    if ($process->isRunning()) {
        $process->stop(3, SIGTERM);
    }
}

function removeFixture(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }
    foreach (new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS) as $file) {
        if ($file->isDir() && ! $file->isLink()) {
            removeFixture($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }
    rmdir($directory);
}

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}
