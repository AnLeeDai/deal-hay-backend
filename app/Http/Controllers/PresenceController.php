<?php

namespace App\Http\Controllers;

use App\Actions\RecordPresenceHeartbeat;
use App\Http\Requests\HeartbeatPresenceRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PresenceController extends Controller
{
    public function __construct(private readonly RecordPresenceHeartbeat $heartbeat) {}

    public function __invoke(HeartbeatPresenceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'ok' => true,
            ...$this->heartbeat->handle($user, $request->validated('conversation_id')),
        ]);
    }
}
