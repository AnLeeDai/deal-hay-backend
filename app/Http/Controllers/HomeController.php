<?php

namespace App\Http\Controllers;

use App\Actions\GetHomePage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private readonly GetHomePage $getHomePage) {}

    public function index(Request $request): JsonResponse
    {
        $selectedCategory = $request->string('category')->trim()->value();

        return response()->json($this->getHomePage->handle($selectedCategory));
    }
}
