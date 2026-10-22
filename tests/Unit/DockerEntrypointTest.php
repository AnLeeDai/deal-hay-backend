<?php

use Symfony\Component\Process\Process;

test('production startup rejects a missing application key before invoking commands', function (): void {
    $result = runDockerEntrypoint(['APP_KEY' => '']);

    expect($result['exit_code'])->toBe(1);
    expect($result['stderr'])->toContain('APP_KEY');
    expect($result['commands'])->toBeEmpty();
});

test('production startup identifies missing Reverb credentials without exposing configured secrets', function (string $missingVariable): void {
    $result = runDockerEntrypoint([
        $missingVariable => '',
        'RUN_MIGRATIONS' => 'true',
    ]);

    expect($result['exit_code'])->toBe(1);
    expect($result['stderr'])
        ->toContain($missingVariable)
        ->not->toContain('test-app-id', 'test-app-key', 'test-app-secret');
    expect($result['commands'])->toBeEmpty();
})->with([
    'application ID missing' => ['REVERB_APP_ID'],
    'application key missing' => ['REVERB_APP_KEY'],
    'application secret missing' => ['REVERB_APP_SECRET'],
]);

test('the Reverb service requires credentials even when its broadcaster uses another driver', function (): void {
    $result = runDockerEntrypoint([
        'DEPLOY_ROLE' => 'reverb',
        'BROADCAST_CONNECTION' => 'log',
        'REVERB_APP_KEY' => '',
    ]);

    expect($result['exit_code'])->toBe(1);
    expect($result['stderr'])->toContain('REVERB_APP_KEY');
    expect($result['commands'])->toBeEmpty();
});

test('an API with another broadcaster starts without Reverb credentials', function (): void {
    $result = runDockerEntrypoint([
        'BROADCAST_CONNECTION' => 'log',
        'REVERB_APP_ID' => '',
        'REVERB_APP_KEY' => '',
        'REVERB_APP_SECRET' => '',
    ]);

    expect($result['exit_code'])->toBe(0);
    expect($result['commands'])->toBe([
        'php artisan optimize --no-interaction',
        'supervisord -c /etc/supervisor/conf.d/deal-hay.conf',
    ]);
});

test('the combined API and worker starts from the environment or explicit role argument', function (array $environment, array $arguments): void {
    $result = runDockerEntrypoint($environment, $arguments);

    expect($result['exit_code'])->toBe(0);
    expect($result['commands'])->toBe([
        'php artisan optimize --no-interaction',
        'supervisord -c /etc/supervisor/conf.d/deal-hay-web-worker.conf',
    ]);
})->with([
    'environment role' => [['DEPLOY_ROLE' => 'web-worker'], ['start']],
    'explicit role overrides environment' => [['DEPLOY_ROLE' => 'web'], ['web-worker']],
]);

test('HTTP concurrency reserves memory for the combined worker role', function (string $role, string $threads): void {
    $result = runDockerEntrypoint(['DEPLOY_ROLE' => $role]);

    expect($result['exit_code'])->toBe(0);
    expect($result['runtime_environment']['FRANKENPHP_NUM_THREADS'])->toBe($threads);
    expect($result['runtime_environment']['FRANKENPHP_MAX_THREADS'])->toBe($threads);
})->with([
    'HTTP service' => ['web', '2'],
    'combined service' => ['web-worker', '1'],
]);

test('worker roles receive bounded memory and recycling defaults', function (string $role): void {
    $result = runDockerEntrypoint(['DEPLOY_ROLE' => $role]);

    expect($result['exit_code'])->toBe(0);
    expect($result['runtime_environment'])->toMatchArray([
        'QUEUE_SLEEP' => '3',
        'QUEUE_MEMORY' => '96',
        'QUEUE_MEMORY_LIMIT' => '128M',
        'QUEUE_MAX_JOBS' => '500',
        'QUEUE_MAX_TIME' => '3600',
    ]);
})->with(['worker', 'web-worker']);

test('runtime limits can be tuned explicitly for larger deployments', function (): void {
    $limits = [
        'FRANKENPHP_NUM_THREADS' => '3',
        'FRANKENPHP_MAX_THREADS' => '4',
        'QUEUE_SLEEP' => '5',
        'QUEUE_MEMORY' => '192',
        'QUEUE_MEMORY_LIMIT' => '256M',
        'QUEUE_MAX_JOBS' => '1000',
        'QUEUE_MAX_TIME' => '7200',
    ];
    $result = runDockerEntrypoint(['DEPLOY_ROLE' => 'web-worker', ...$limits]);

    expect($result['exit_code'])->toBe(0);
    expect($result['runtime_environment'])->toBe($limits);
});

test('invalid runtime limits fail before service startup', function (array $limits, string $variable): void {
    $result = runDockerEntrypoint(['DEPLOY_ROLE' => 'web-worker', ...$limits]);

    expect($result['exit_code'])->toBe(1);
    expect($result['stderr'])->toContain($variable);
    expect($result['commands'])->toBeEmpty();
})->with([
    'zero HTTP threads' => [['FRANKENPHP_NUM_THREADS' => '0'], 'FRANKENPHP_NUM_THREADS'],
    'HTTP maximum below initial threads' => [['FRANKENPHP_NUM_THREADS' => '3', 'FRANKENPHP_MAX_THREADS' => '2'], 'FRANKENPHP_MAX_THREADS'],
    'busy polling' => [['QUEUE_SLEEP' => '0'], 'QUEUE_SLEEP'],
    'non-numeric polling interval' => [['QUEUE_SLEEP' => 'invalid'], 'QUEUE_SLEEP'],
    'negative worker memory' => [['QUEUE_MEMORY' => '-1'], 'QUEUE_MEMORY'],
    'negative recycling interval' => [['QUEUE_MAX_TIME' => '-1'], 'QUEUE_MAX_TIME'],
]);

test('opt-in API migrations complete before optimization and service startup', function (string $role, string $flag, string $supervisorConfiguration): void {
    $result = runDockerEntrypoint([
        'DEPLOY_ROLE' => $role,
        'RUN_MIGRATIONS' => $flag,
    ]);

    expect($result['exit_code'])->toBe(0);
    expect($result['commands'])->toBe([
        'php artisan migrate --force --no-interaction',
        'php artisan optimize --no-interaction',
        'supervisord -c '.$supervisorConfiguration,
    ]);
})->with([
    'web with true' => ['web', 'true', '/etc/supervisor/conf.d/deal-hay.conf'],
    'combined service with 1' => ['web-worker', '1', '/etc/supervisor/conf.d/deal-hay-web-worker.conf'],
]);

test('API startup skips migrations when they are disabled', function (string $flag): void {
    $result = runDockerEntrypoint(['RUN_MIGRATIONS' => $flag]);

    expect($result['exit_code'])->toBe(0);
    expect($result['commands'])->toBe([
        'php artisan optimize --no-interaction',
        'supervisord -c /etc/supervisor/conf.d/deal-hay.conf',
    ]);
})->with([
    'false' => ['false'],
    '0' => ['0'],
    'empty' => [''],
]);

test('a migration failure prevents optimization and service startup', function (): void {
    $result = runDockerEntrypoint([
        'RUN_MIGRATIONS' => 'true',
        'TEST_FAIL_ARTISAN_COMMAND' => 'migrate',
    ]);

    expect($result['exit_code'])->toBe(42);
    expect($result['commands'])->toBe(['php artisan migrate --force --no-interaction']);
});

test('non-API services reject startup migrations before invoking commands', function (string $role): void {
    $result = runDockerEntrypoint([
        'DEPLOY_ROLE' => $role,
        'RUN_MIGRATIONS' => 'true',
    ]);

    expect($result['exit_code'])->toBe(1);
    expect($result['stderr'])->toContain('RUN_MIGRATIONS', 'web', 'web-worker');
    expect($result['commands'])->toBeEmpty();
})->with([
    'Reverb' => ['reverb'],
    'background worker' => ['worker'],
]);

test('an invalid migration flag prevents startup', function (): void {
    $result = runDockerEntrypoint(['RUN_MIGRATIONS' => 'yes']);

    expect($result['exit_code'])->toBe(1);
    expect($result['stderr'])->toContain('RUN_MIGRATIONS');
    expect($result['commands'])->toBeEmpty();
});

test('an unsupported startup role is rejected before credential checks and commands', function (): void {
    $result = runDockerEntrypoint([
        'DEPLOY_ROLE' => 'unknown',
        'REVERB_APP_KEY' => '',
    ]);

    expect($result['exit_code'])->toBe(1);
    expect($result['stderr'])->toContain('Unsupported DEPLOY_ROLE: unknown');
    expect($result['commands'])->toBeEmpty();
});

test('Reverb binds to the public container port before optimization and launch', function (string|false $port, string $expectedPort): void {
    $result = runDockerEntrypoint([
        'DEPLOY_ROLE' => 'reverb',
        'PORT' => $port,
        'REVERB_SERVER_HOST' => '127.0.0.1',
        'REVERB_SERVER_PORT' => '8081',
    ]);

    expect($result['exit_code'])->toBe(0);
    expect($result['commands'])->toBe([
        'php artisan optimize --no-interaction',
        'php artisan reverb:start --host=0.0.0.0 --port='.$expectedPort.' --no-interaction',
    ]);
    expect($result['server_bindings'])->toBe([
        ['host' => '0.0.0.0', 'port' => $expectedPort],
        ['host' => '0.0.0.0', 'port' => $expectedPort],
    ]);
})->with([
    'Render provided port' => ['12345', '12345'],
    'default port' => [false, '10000'],
]);

test('an explicit command bypasses service role and Reverb startup checks', function (): void {
    $result = runDockerEntrypoint([
        'DEPLOY_ROLE' => 'unknown',
        'REVERB_APP_KEY' => '',
        'RUN_MIGRATIONS' => 'true',
    ], ['php', '--version']);

    expect($result['exit_code'])->toBe(0);
    expect($result['commands'])->toBe(['php --version']);
});

test('an optimization failure prevents service startup', function (): void {
    $result = runDockerEntrypoint(['TEST_FAIL_ARTISAN_COMMAND' => 'optimize']);

    expect($result['exit_code'])->toBe(42);
    expect($result['commands'])->toBe(['php artisan optimize --no-interaction']);
});

/**
 * @param  array<string, string|false>  $environment
 * @param  list<string>  $arguments
 * @return array{exit_code: ?int, stderr: string, commands: list<string>, server_bindings: list<array{host: string, port: string}>, runtime_environment: array<string, string>}
 */
function runDockerEntrypoint(array $environment = [], array $arguments = ['start']): array
{
    $fixtureDirectory = sys_get_temp_dir().'/deal-hay-entrypoint-'.bin2hex(random_bytes(8));
    mkdir($fixtureDirectory.'/bin', 0700, true);
    $commandLog = $fixtureDirectory.'/commands.log';
    $runtimeLog = $fixtureDirectory.'/runtime.log';

    $fakeCommand = <<<'SH'
#!/bin/sh
set -eu
printf '%s\t%s\t%s\n' "${0##*/} $*" "${REVERB_SERVER_HOST:-}" "${REVERB_SERVER_PORT:-}" >> "$TEST_COMMAND_LOG"
printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\n' "${FRANKENPHP_NUM_THREADS:-}" "${FRANKENPHP_MAX_THREADS:-}" "${QUEUE_SLEEP:-}" "${QUEUE_MEMORY:-}" "${QUEUE_MEMORY_LIMIT:-}" "${QUEUE_MAX_JOBS:-}" "${QUEUE_MAX_TIME:-}" >> "$TEST_RUNTIME_LOG"
if [ "${1:-}" = "artisan" ] && [ "${2:-}" = "${TEST_FAIL_ARTISAN_COMMAND:-}" ]; then
    exit 42
fi
SH;

    foreach (['php', 'supervisord'] as $command) {
        file_put_contents($fixtureDirectory.'/bin/'.$command, $fakeCommand);
        chmod($fixtureDirectory.'/bin/'.$command, 0700);
    }

    try {
        $process = new Process(
            ['sh', dirname(__DIR__, 2).'/docker-entrypoint.sh', ...$arguments],
            null,
            array_replace([
                'PATH' => $fixtureDirectory.'/bin:'.getenv('PATH'),
                'APP_BASE_PATH' => $fixtureDirectory,
                'APP_ENV' => 'production',
                'APP_KEY' => 'test-application-key',
                'DEPLOY_ROLE' => 'web',
                'BROADCAST_CONNECTION' => 'reverb',
                'REVERB_APP_ID' => 'test-app-id',
                'REVERB_APP_KEY' => 'test-app-key',
                'REVERB_APP_SECRET' => 'test-app-secret',
                'REVERB_SERVER_HOST' => '',
                'REVERB_SERVER_PORT' => '',
                'RUN_MIGRATIONS' => 'false',
                'TEST_COMMAND_LOG' => $commandLog,
                'TEST_RUNTIME_LOG' => $runtimeLog,
                'TEST_FAIL_ARTISAN_COMMAND' => '',
                'FRANKENPHP_NUM_THREADS' => false,
                'FRANKENPHP_MAX_THREADS' => false,
                'QUEUE_SLEEP' => false,
                'QUEUE_MEMORY' => false,
                'QUEUE_MEMORY_LIMIT' => false,
                'QUEUE_MAX_JOBS' => false,
                'QUEUE_MAX_TIME' => false,
            ], $environment),
            timeout: 5,
        );
        $process->run();

        $executions = file_exists($commandLog)
            ? file($commandLog, FILE_IGNORE_NEW_LINES)
            : [];
        $commands = [];
        $serverBindings = [];
        $runtimeExecutions = file_exists($runtimeLog)
            ? file($runtimeLog, FILE_IGNORE_NEW_LINES)
            : [];
        $runtimeEnvironment = $runtimeExecutions === [] ? [] : array_combine([
            'FRANKENPHP_NUM_THREADS',
            'FRANKENPHP_MAX_THREADS',
            'QUEUE_SLEEP',
            'QUEUE_MEMORY',
            'QUEUE_MEMORY_LIMIT',
            'QUEUE_MAX_JOBS',
            'QUEUE_MAX_TIME',
        ], explode("\t", end($runtimeExecutions)));

        foreach ($executions as $execution) {
            [$command, $host, $port] = explode("\t", $execution);
            $commands[] = $command;
            $serverBindings[] = ['host' => $host, 'port' => $port];
        }

        return [
            'exit_code' => $process->getExitCode(),
            'stderr' => $process->getErrorOutput(),
            'commands' => $commands,
            'server_bindings' => $serverBindings,
            'runtime_environment' => $runtimeEnvironment,
        ];
    } finally {
        foreach (['bin/php', 'bin/supervisord', 'commands.log', 'runtime.log'] as $file) {
            if (file_exists($fixtureDirectory.'/'.$file)) {
                unlink($fixtureDirectory.'/'.$file);
            }
        }

        rmdir($fixtureDirectory.'/bin');
        rmdir($fixtureDirectory);
    }
}
