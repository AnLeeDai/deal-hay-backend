<?php

namespace App\Http\Controllers;

use App\Services\AddressService;
use Illuminate\Http\JsonResponse;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses) {}

    public function provinces(): JsonResponse
    {
        return response()->json($this->addresses->provinces());
    }

    public function wards(int $provinceCode): JsonResponse
    {
        return response()->json($this->addresses->wards($provinceCode));
    }
}
