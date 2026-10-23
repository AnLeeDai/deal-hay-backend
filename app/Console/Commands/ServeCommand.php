<?php

namespace App\Console\Commands;

use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;

class ServeCommand extends BaseServeCommand
{
    protected function serverCommand(): array
    {
        $command = parent::serverCommand();

        // PHP parses multipart uploads before Laravel can apply its validation rules.
        array_splice($command, 1, 0, [
            '-d', 'memory_limit=128M',
            '-d', 'upload_max_filesize=3M',
            '-d', 'post_max_size=32M',
            '-d', 'max_file_uploads=51',
        ]);

        return $command;
    }
}
