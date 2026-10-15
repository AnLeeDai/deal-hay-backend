<?php

namespace App\Http\Controllers;

use App\Actions\GetAdminUsers;
use App\Actions\StartAdminChat;
use App\Actions\ToggleUserLock;
use App\Http\Requests\AdminToggleLockUserRequest;
use App\Http\Requests\StartAdminChatRequest;
use App\Models\User;
use App\Services\ChatPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class AdminUserController extends Controller
{
    public function __construct(
        private readonly GetAdminUsers $users,
        private readonly ToggleUserLock $toggleUserLock,
    ) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'users' => $this->users->handle(),
        ]);
    }

    public function toggleLock(AdminToggleLockUserRequest $request, User $user): JsonResponse
    {
        if (! $this->toggleUserLock->handle($user, $request->validated())) {
            throw ValidationException::withMessages([
                'general' => 'Không thể khóa tài khoản quản trị viên.',
            ]);
        }

        return response()->json([
            'message' => 'Cập nhật trạng thái tài khoản thành công.',
            'data' => $user->refresh(),
        ]);
    }

    public function startChat(
        StartAdminChatRequest $request,
        User $user,
        StartAdminChat $startChat,
        ChatPayloadService $payloads,
    ): JsonResponse {
        $conversation = $startChat->handle($request->user(), $user);

        return response()->json([
            'conversation' => $payloads->conversation($conversation, $request->user()),
        ], $conversation->wasRecentlyCreated ? 201 : 200);
    }
}
