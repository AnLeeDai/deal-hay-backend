<?php

namespace App\Models\Traits;

use App\Services\MediaClient;

trait HasRemoteMedia
{
    public function toArray(): array
    {
        $array = parent::toArray();

        if (! isset($this->mediaAttributes) || empty($this->mediaAttributes)) {
            return $array;
        }

        foreach ($this->mediaAttributes as $attribute) {
            if (isset($this->attributes[$attribute])) {
                $array[$attribute] = app(MediaClient::class)->url(
                    $this->attributes[$attribute],
                );
            }
        }

        return $array;
    }
}
