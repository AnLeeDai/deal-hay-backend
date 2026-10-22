<?php

use App\Models\User;
use App\Services\AddressService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;

it('validates and formats a warmed address without querying the database', function (): void {
    $addresses = app(AddressService::class);
    $addresses->provinces();
    $addresses->wards(1);
    DB::enableQueryLog();

    try {
        expect($addresses->provinceCode('Thành phố Hà Nội'))->toBe(1);
        expect($addresses->provinceCode('01', true))->toBe(1);
        expect($addresses->wardBelongsToProvince('Phường Hoàn Kiếm', 'Thành phố Hà Nội'))->toBeTrue();
        expect($addresses->wardBelongsToProvince('00070', '01', true))->toBeTrue();
        expect($addresses->formatShippingAddress(' 12 Hàng Bài ', 1, 70))
            ->toBe('12 Hàng Bài, Phường Hoàn Kiếm, Thành phố Hà Nội');
        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($queries)->toBe([]);
});

it('saves an address from warmed reference lists without querying the addresses table', function (): void {
    $user = User::factory()->customer()->create();
    Sanctum::actingAs($user);
    $this->getJson('/api/addresses/provinces')->assertOk();
    $this->getJson('/api/addresses/provinces/1/wards')->assertOk();
    DB::enableQueryLog();

    try {
        $this->patchJson('/api/private/profile/me', [
            'province' => 'Thành phố Hà Nội',
            'ward' => 'Phường Hoàn Kiếm',
            'address_detail' => '12 Hàng Bài',
        ])->assertOk();
        $addressQueries = collect(DB::getQueryLog())->filter(
            fn (array $query): bool => str_contains($query['query'], 'addresses'),
        );
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($addressQueries)->toHaveCount(0);
    $this->assertDatabaseHas('users', [
        'id' => $user->getKey(),
        'province' => 'Thành phố Hà Nội',
        'ward' => 'Phường Hoàn Kiếm',
        'address_detail' => '12 Hàng Bài',
    ]);
});

it('rejects invalid and mismatching ward selections from reference data', function (mixed $ward, mixed $province, bool $useCodes): void {
    $addresses = app(AddressService::class);

    expect($addresses->wardBelongsToProvince($ward, $province, $useCodes))->toBeFalse();
})->with([
    'ward name from another province' => ['Phường Hoàn Kiếm', 'Tỉnh Cao Bằng', false],
    'ward code from another province' => [70, 4, true],
    'province name used as ward' => ['Thành phố Hà Nội', 'Thành phố Hà Nội', false],
    'province code used as ward' => [1, 1, true],
    'unknown ward name' => ['Phường tự điền', 'Thành phố Hà Nội', false],
    'nonnumeric ward code' => ['70invalid', 1, true],
    'malformed ward selection' => [[], 1, true],
    'unknown province code' => [70, 999999, true],
]);

it('rejects shipping addresses containing a ward outside the selected province', function (): void {
    $addresses = app(AddressService::class);

    expect(fn (): string => $addresses->formatShippingAddress('12 Hàng Bài', 4, 70))
        ->toThrow(ValidationException::class, 'Vui lòng chọn phường/xã thuộc tỉnh/thành đã chọn.');
});

it('normalizes reference lists cached by older versions without querying the database', function (): void {
    Cache::store('array')->put('addresses.provinces', collect([
        (object) ['code' => '1', 'name' => ' Thành phố Hà Nội '],
    ]), 60);
    Cache::store('array')->put('addresses.wards.1', collect([
        (object) ['code' => '70', 'name' => ' Phường Hoàn Kiếm ', 'division_type' => ' phường '],
    ]), 60);
    $addresses = app(AddressService::class);
    DB::enableQueryLog();

    try {
        $this->getJson('/api/addresses/provinces')
            ->assertExactJson([['code' => 1, 'name' => 'Thành phố Hà Nội']]);
        $this->getJson('/api/addresses/provinces/1/wards')
            ->assertExactJson([['code' => 70, 'name' => 'Phường Hoàn Kiếm', 'division_type' => 'phường']]);
        expect($addresses->formatShippingAddress('12 Hàng Bài', 1, 70))
            ->toBe('12 Hàng Bài, Phường Hoàn Kiếm, Thành phố Hà Nội');
        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($queries)->toBe([]);
});

it('keeps reference cache reads and writes independent of the shared database cache', function (): void {
    config(['cache.default' => 'database']);
    $addresses = app(AddressService::class);
    DB::enableQueryLog();

    try {
        $address = $addresses->formatShippingAddress('12 Hàng Bài', 1, 70);
        $queries = collect(DB::getQueryLog());
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($address)->toBe('12 Hàng Bài, Phường Hoàn Kiếm, Thành phố Hà Nội');
    expect($queries)->toHaveCount(2);
    expect($queries->every(fn (array $query): bool => str_contains($query['query'], 'addresses')))->toBeTrue();
    expect(Cache::store('array')->has('addresses.provinces'))->toBeTrue();
    expect(Cache::store('array')->has('addresses.wards.1'))->toBeTrue();
});

it('does not query or cache wards for unknown provinces after provinces are warmed', function (): void {
    $addresses = app(AddressService::class);
    $addresses->provinces();
    DB::enableQueryLog();

    try {
        expect($addresses->wards(999999))->toBe([]);
        expect($addresses->wards(999998))->toBe([]);
        $queries = DB::getQueryLog();
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($queries)->toBe([]);
    expect(Cache::store('array')->has('addresses.wards.999999'))->toBeFalse();
    expect(Cache::store('array')->has('addresses.wards.999998'))->toBeFalse();
});
