<?php

namespace App\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddressService
{
    private const int CACHE_TTL_SECONDS = 86400;

    private const array PROVINCE_DIVISION_TYPES = ['tỉnh', 'thành phố trung ương'];

    private const array WARD_DIVISION_TYPES = ['phường', 'xã', 'đặc khu'];

    public function provinceCode(mixed $province, bool $useCodes = false): ?int
    {
        return $this->findProvince($province, $useCodes)['code'] ?? null;
    }

    public function wardBelongsToProvince(mixed $ward, mixed $province, bool $useCodes = false): bool
    {
        $provinceCode = $this->provinceCode($province, $useCodes);

        return $provinceCode !== null && $this->findWard($ward, $provinceCode, $useCodes) !== null;
    }

    public function formatShippingAddress(string $detail, int $provinceCode, int $wardCode): string
    {
        $province = $this->findProvince($provinceCode, true);
        $ward = $province === null ? null : $this->findWard($wardCode, $provinceCode, true);

        if ($ward === null) {
            throw ValidationException::withMessages([
                'shipping_ward_code' => 'Vui lòng chọn phường/xã thuộc tỉnh/thành đã chọn.',
            ]);
        }

        return implode(', ', [trim($detail), $ward['name'], $province['name']]);
    }

    /** @return array<int, array{code:int,name:string}> */
    public function provinces(): array
    {
        $cacheKey = 'addresses.provinces';
        $cache = $this->referenceCache();
        $cached = $this->normalizeProvinces($cache->get($cacheKey));

        if ($cached !== []) {
            return $cached;
        }

        $provinces = DB::table('addresses')
            ->select(['code', 'name'])
            ->whereIn('division_type', self::PROVINCE_DIVISION_TYPES)
            ->orderBy('name')
            ->get()
            ->map(fn ($province): array => [
                'code' => (int) $province->code,
                'name' => (string) $province->name,
            ])->all();

        if ($provinces !== []) {
            $cache->put($cacheKey, $provinces, self::CACHE_TTL_SECONDS);
        }

        return $provinces;
    }

    /** @return array<int, array{code:int,name:string,division_type:string}> */
    public function wards(int $provinceCode): array
    {
        if ($this->provinceCode($provinceCode, true) === null) {
            return [];
        }

        $cacheKey = sprintf('addresses.wards.%d', $provinceCode);
        $cache = $this->referenceCache();
        $wards = $this->normalizeWards($cache->get($cacheKey));

        if ($wards !== []) {
            return $wards;
        }

        $wards = DB::table('addresses')
            ->select(['code', 'name', 'division_type'])
            ->where('parent_code', $provinceCode)
            ->whereIn('division_type', self::WARD_DIVISION_TYPES)
            ->orderBy('name')
            ->get()
            ->map(fn ($ward): array => [
                'code' => (int) $ward->code,
                'name' => (string) $ward->name,
                'division_type' => (string) $ward->division_type,
            ])->all();

        if ($wards !== []) {
            $cache->put($cacheKey, $wards, self::CACHE_TTL_SECONDS);
        }

        return $wards;
    }

    /** @return array{code:int,name:string}|null */
    private function findProvince(mixed $province, bool $useCodes): ?array
    {
        if ((! is_string($province) && ! is_int($province)) || ($useCodes && ! ctype_digit((string) $province))) {
            return null;
        }

        $value = $useCodes ? (int) $province : (string) $province;

        foreach ($this->provinces() as $candidate) {
            if ($candidate[$useCodes ? 'code' : 'name'] === $value) {
                return $candidate;
            }
        }

        return null;
    }

    /** @return array{code:int,name:string,division_type:string}|null */
    private function findWard(mixed $ward, int $provinceCode, bool $useCodes): ?array
    {
        if ((! is_string($ward) && ! is_int($ward)) || ($useCodes && ! ctype_digit((string) $ward))) {
            return null;
        }

        $value = $useCodes ? (int) $ward : (string) $ward;

        foreach ($this->wards($provinceCode) as $candidate) {
            if ($candidate[$useCodes ? 'code' : 'name'] === $value) {
                return $candidate;
            }
        }

        return null;
    }

    private function referenceCache(): Repository
    {
        return Cache::memo((string) config('cache.reference_store', 'file'));
    }

    /** @return array<int, array{code:int,name:string}> */
    private function normalizeProvinces(mixed $data): array
    {
        return collect($data)->map(fn ($province): array => [
            'code' => (int) data_get($province, 'code'),
            'name' => trim((string) data_get($province, 'name')),
        ])->filter(fn (array $province): bool => $province['code'] > 0 && $province['name'] !== '')->values()->all();
    }

    /** @return array<int, array{code:int,name:string,division_type:string}> */
    private function normalizeWards(mixed $data): array
    {
        return collect($data)->map(fn ($ward): array => [
            'code' => (int) data_get($ward, 'code'),
            'name' => trim((string) data_get($ward, 'name')),
            'division_type' => trim((string) data_get($ward, 'division_type')),
        ])->filter(fn (array $ward): bool => $ward['code'] > 0
            && $ward['name'] !== ''
            && in_array($ward['division_type'], self::WARD_DIVISION_TYPES, true))
            ->values()->all();
    }
}
