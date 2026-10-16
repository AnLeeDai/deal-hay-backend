<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdGemPostbackRequest;
use App\Services\RewardedTaskService;
use Illuminate\Http\JsonResponse;

class AdGemPostbackController extends Controller
{
    public function __invoke(AdGemPostbackRequest $request, RewardedTaskService $tasks): JsonResponse
    {
        return response()->json(['status' => $tasks->receiveAdGemConversion($request->validated())]);
    }
}
