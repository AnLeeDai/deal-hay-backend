<?php

namespace App\Console\Commands;

use App\Services\ImageService;
use Illuminate\Foundation\Console\ServeCommand as BaseServeCommand;

class ServeCommand extends BaseServeCommand
{
    protected function serverCommand(): array
    {
        $command = parent::serverCommand();

        // PHP parses multipart uploads before Laravel can apply its validation rules.
        array_splice($command, 1, 0, [
            '-d', 'upload_max_filesize='.ImageService::MAX_SOURCE_BYTES,
            '-d', 'post_max_size=40M',
            '-d', 'max_file_uploads=51',
        ]);

        return $command;
    }
}
