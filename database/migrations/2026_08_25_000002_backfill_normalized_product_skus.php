<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shop_product_variants')
            ->whereNull('shop_owner_id')
            ->orderBy('id')
            ->chunkById(200, function ($variants): void {
                $ownerIds = DB::table('shop_products')
                    ->whereIn('id', $variants->pluck('shop_product_id'))
                    ->pluck('shop_owner_id', 'id');

                foreach ($variants as $variant) {
                    $shopOwnerId = $ownerIds->get($variant->shop_product_id);

                    if ($shopOwnerId !== null) {
                        DB::table('shop_product_variants')
                            ->where('id', $variant->id)
                            ->update(['shop_owner_id' => $shopOwnerId]);
                    }
                }
            });

        $this->backfillCatalogSkus();

        DB::table('shop_products')
            ->whereExists(function ($query): void {
                $query
                    ->select('shop_product_variants.id')
                    ->from('shop_product_variants')
                    ->whereColumn('shop_product_variants.shop_product_id', 'shop_products.id');
            })
            ->update(['has_variants' => true]);

    }

    public function down(): void
    {
        DB::table('shop_product_variants')->update([
            'shop_owner_id' => null,
            'sku_normalized' => null,
        ]);
        DB::table('shop_products')->update(['sku_normalized' => null]);
        DB::table('shop_products')->update(['has_variants' => false]);
    }

    private function backfillCatalogSkus(): void
    {
        $ownerIds = DB::table('shop_products')
            ->select('shop_owner_id')
            ->distinct()
            ->orderBy('shop_owner_id')
            ->pluck('shop_owner_id');

        foreach ($ownerIds as $ownerId) {
            $usedSkus = [];
            $this->backfillTableSkus('shop_products', (string) $ownerId, 'P', $usedSkus);
            $this->backfillTableSkus('shop_product_variants', (string) $ownerId, 'V', $usedSkus);
        }
    }

    /** @param array<string, true> $usedSkus */
    private function backfillTableSkus(string $table, string $ownerId, string $type, array &$usedSkus): void
    {
        DB::table($table)
            ->select(['id', 'sku'])
            ->where('shop_owner_id', $ownerId)
            ->orderBy('id')
            ->chunkById(200, function ($records) use (&$usedSkus, $table, $type): void {
                foreach ($records as $record) {
                    $sku = $this->uniqueCanonicalSku($record->sku, $type, (string) $record->id, $usedSkus);

                    DB::table($table)
                        ->where('id', $record->id)
                        ->update(['sku_normalized' => $sku]);
                }
            });
    }

    /** @param array<string, true> $usedSkus */
    private function uniqueCanonicalSku(mixed $rawSku, string $type, string $id, array &$usedSkus): string
    {
        $token = Str::upper(Str::substr(str_replace('-', '', $id), 0, 12));
        $base = Str::upper(Str::ascii(trim((string) $rawSku)));
        $base = preg_replace('/[^A-Z0-9._-]+/', '-', $base) ?? '';
        $base = ltrim($base, '._-');
        $base = Str::substr($base, 0, 80);

        if ($base === '') {
            $base = ($type === 'P' ? 'PRODUCT-' : 'VARIANT-').$token;
        }

        $candidate = $base;
        $sequence = 1;

        while (isset($usedSkus[$candidate])) {
            $suffix = '-'.$type.'-'.$token.($sequence > 1 ? '-'.$sequence : '');
            $candidate = Str::substr($base, 0, 80 - strlen($suffix)).$suffix;
            $sequence++;
        }

        $usedSkus[$candidate] = true;

        return $candidate;
    }
};
