<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

function preparedImage(string $name = 'prepared.webp'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, file_get_contents(__DIR__.'/Fixtures/prepared.webp'));
}
