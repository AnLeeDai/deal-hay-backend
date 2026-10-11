<?php

namespace App\Http\Controllers;

use App\Actions\UpdateUserPassword;
use App\Actions\UpdateUserProfile;
use App\Http\Requests\UpdateProfilePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use App\Services\UserSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(private readonly UserSessionService $sessions) {}

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'auth' => [
                'user' => $user->toArray(),
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateUserProfile $updateUserProfile): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $updateUserProfile->handle($user, $request->validated());

        return response()->json([
            'message' => 'Cập nhật thông tin thành công.',
            'data' => $user->refresh(),
        ]);
    }

    public function updatePassword(UpdateProfilePasswordRequest $request, UpdateUserPassword $updateUserPassword): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;
        $updateUserPassword->handle($user, (string) $request->validated('password'), $sessionId);

        if ($request->hasSession()) {
            $request->session()->regenerate();
            $request->session()->put(
                UserSessionService::VERSION_KEY,
                $this->sessions->markCurrent($user, $request->session()->getId()),
            );
        }

        return response()->json([
            'message' => 'Cập nhật mật khẩu thành công.',
        ]);
    }
}
