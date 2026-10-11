<?php

namespace App\Http\Controllers;

use App\Actions\GetPublicProductPage;
use App\Actions\GetPublicShopPage;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicShopController extends Controller
{
    public function __construct(
        private readonly GetPublicShopPage $getPublicShopPage,
        private readonly GetPublicProductPage $getPublicProductPage,
    ) {}

    public function show(Request $request, User $shop): JsonResponse
    {
        /** @var User|null $viewer */
        $viewer = $request->user('sanctum');

        return response()->json($this->getPublicShopPage->handle($shop, $viewer));
    }

    public function showProduct(Request $request, User $shop, ShopProduct $shopProduct): JsonResponse
    {
        /** @var User|null $viewer */
        $viewer = $request->user('sanctum');

        return response()->json($this->getPublicProductPage->handle($shop, $shopProduct, $viewer));
    }
}
