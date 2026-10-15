<?php

namespace App\Services;

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
        if ((! is_string($province) && ! is_int($province)) || ($useCodes && ! ctype_digit((string) $province))) {
            return null;
        }

        $code = DB::table('addresses')
            ->whereIn('division_type', self::PROVINCE_DIVISION_TYPES)
            ->where($useCodes ? 'code' : 'name', $province)
            ->value('code');

        return $code === null ? null : (int) $code;
    }

    public function wardBelongsToProvince(mixed $ward, mixed $province, bool $useCodes = false): bool
    {
        $provinceCode = $this->provinceCode($province, $useCodes);

        if ($provinceCode === null || (! is_string($ward) && ! is_int($ward)) || ($useCodes && ! ctype_digit((string) $ward))) {
            return false;
        }

        return DB::table('addresses')
            ->whereIn('division_type', self::WARD_DIVISION_TYPES)
            ->where('parent_code', $provinceCode)
            ->where($useCodes ? 'code' : 'name', $ward)
            ->exists();
    }

    public function formatShippingAddress(string $detail, int $provinceCode, int $wardCode): string
    {
        if (! $this->wardBelongsToProvince($wardCode, $provinceCode, true)) {
            throw ValidationException::withMessages([
                'shipping_ward_code' => 'Vui lòng chọn phường/xã thuộc tỉnh/thành đã chọn.',
            ]);
        }

        $names = DB::table('addresses')->whereIn('code', [$provinceCode, $wardCode])->pluck('name', 'code');

        return implode(', ', [trim($detail), $names[$wardCode], $names[$provinceCode]]);
    }

    /** @return array<int, array{code:int,name:string}> */
    public function provinces(): array
    {
        $cacheKey = 'addresses.provinces';
        $cached = $this->normalizeProvinces(Cache::get($cacheKey));

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

        Cache::put($cacheKey, $provinces, self::CACHE_TTL_SECONDS);

        return $provinces;
    }

    /** @return array<int, array{code:int,name:string,division_type:string}> */
    public function wards(int $provinceCode): array
    {
        $cacheKey = sprintf('addresses.wards.%d', $provinceCode);
        $wards = $this->normalizeWards(Cache::get($cacheKey));

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

        Cache::put($cacheKey, $wards, self::CACHE_TTL_SECONDS);

        return $wards;
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
