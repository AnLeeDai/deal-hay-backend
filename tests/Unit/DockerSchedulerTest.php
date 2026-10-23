<?php

use Symfony\Component\Process\Process;

test('the scheduler aligns minute launches and keeps running after a failed command', function (): void {
    $directory = schedulerFixtureDirectory();
    schedulerFixtureCommand($directory, 'php', <<<'SH'
printf '%s\t%s\n' "$PWD" "$*" >> "$TEST_COMMAND_LOG"
count=$(wc -l < "$TEST_COMMAND_LOG")
if [ "$count" -eq 1 ]; then
    exit 42
fi
kill -TERM "$PPID"
SH);

    try {
        $process = schedulerFixtureProcess($directory);
        $process->mustRun();

        $commands = file($directory.'/commands.log', FILE_IGNORE_NEW_LINES);
        $delays = file($directory.'/delays.log', FILE_IGNORE_NEW_LINES);

        expect($commands)->toHaveCount(2);
        expect($commands)->each->toBe($directory."\t-d memory_limit=128M artisan schedule:run --no-interaction");
        expect($delays)->each->toBe('1');
    } finally {
        removeSchedulerFixture($directory);
    }
});

test('the scheduler stops its active command and reaps it on termination', function (): void {
    $directory = schedulerFixtureDirectory();
    schedulerFixtureCommand($directory, 'php', <<<'SH'
trap 'printf stopped > "$TEST_STOP_LOG"; exit 0' TERM
printf '%s' "$$" > "$TEST_COMMAND_LOG"
kill -TERM "$PPID"
while :; do
    /bin/sleep 0.02
done
SH);

    try {
        $process = schedulerFixtureProcess($directory);
        $process->mustRun();

        expect(file_get_contents($directory.'/stopped.log'))->toBe('stopped');

        $childId = (int) file_get_contents($directory.'/commands.log');
        $childCheck = new Process(['kill', '-0', (string) $childId]);
        $childCheck->run();

        expect($childCheck->isSuccessful())->toBeFalse();
    } finally {
        removeSchedulerFixture($directory);
    }
});

function schedulerFixtureDirectory(): string
{
    $directory = sys_get_temp_dir().'/deal-hay-scheduler-'.bin2hex(random_bytes(8));
    mkdir($directory.'/bin', 0700, true);
    schedulerFixtureCommand($directory, 'date', "printf '119\\n'");
    schedulerFixtureCommand($directory, 'sleep', <<<'SH'
printf '%s\n' "$1" >> "$TEST_DELAY_LOG"
exec /bin/sleep 0.02
SH);

    return $directory;
}

function schedulerFixtureCommand(string $directory, string $command, string $contents): void
{
    file_put_contents($directory.'/bin/'.$command, "#!/bin/sh\nset -eu\n".$contents."\n");
    chmod($directory.'/bin/'.$command, 0700);
}

function schedulerFixtureProcess(string $directory): Process
{
    return new Process(
        ['sh', dirname(__DIR__, 2).'/docker-scheduler.sh'],
        env: [
            'PATH' => $directory.'/bin:'.getenv('PATH'),
            'APP_BASE_PATH' => $directory,
            'SCHEDULER_MEMORY_LIMIT' => false,
            'TEST_COMMAND_LOG' => $directory.'/commands.log',
            'TEST_DELAY_LOG' => $directory.'/delays.log',
            'TEST_STOP_LOG' => $directory.'/stopped.log',
        ],
        timeout: 5,
    );
}

function removeSchedulerFixture(string $directory): void
{
    foreach (glob($directory.'/bin/*') as $file) {
        unlink($file);
    }

    rmdir($directory.'/bin');

    foreach (glob($directory.'/*') as $file) {
        unlink($file);
    }

    rmdir($directory);
}
