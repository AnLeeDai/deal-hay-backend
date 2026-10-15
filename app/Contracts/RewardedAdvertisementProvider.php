<?php

namespace App\Contracts;

use App\Models\ProductAdView;

interface RewardedAdvertisementProvider
{
    /** @return array<string, mixed> Public launch data, without credentials or server verification secrets. */
    public function start(ProductAdView $view): array;

    /** Return a trusted provider event reference bound to this session and its account; client claims are insufficient. */
    public function verifiedCompletionReference(ProductAdView $view): ?string;
}
