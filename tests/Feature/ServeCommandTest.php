<?php

use App\Console\Commands\ServeCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Process\Process;

it('starts PHP with limits accepting 51 image parts and a 32 MiB product payload', function (): void {
    $command = new class extends ServeCommand
    {
        public function phpCommand(): array
        {
            $this->input = new ArrayInput(['--host' => '127.0.0.1', '--port' => 8000], $this->getDefinition());

            return $this->serverCommand();
        }
    };
    $serverCommand = $command->phpCommand();
    $process = new Process([
        ...array_slice($serverCommand, 0, array_search('-S', $serverCommand, true)),
        '-r',
        'echo json_encode(["files" => (int) ini_get("max_file_uploads"), "post_bytes" => ini_parse_quantity(ini_get("post_max_size")), "source_bytes" => ini_parse_quantity(ini_get("upload_max_filesize")), "memory_bytes" => ini_parse_quantity(ini_get("memory_limit"))], JSON_THROW_ON_ERROR);',
    ]);

    $process->mustRun();

    expect(json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR))->toBe([
        'files' => 51,
        'post_bytes' => 41_943_040,
        'source_bytes' => 31_457_280,
        'memory_bytes' => 268_435_456,
    ]);
});
