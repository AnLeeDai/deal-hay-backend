<?php

namespace App\Services;

/** Identity asserted by the authenticated application; no shared user database. */
final readonly class UploadOwner
{
    public function __construct(public string $id, public string $user_code) {}

    public function getKey(): string
    {
        return $this->id;
    }
}
