<?php

namespace App\Http\Middleware;

use App\Enums\UserStatusEnum;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || (int) $user->is_active === UserStatusEnum::ACTIVE) {
            return $next($request);
        }

        $currentAccessToken = $user->currentAccessToken();

        if ($currentAccessToken instanceof PersonalAccessToken) {
            $currentAccessToken->delete();
        }

        return new JsonResponse([
            'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.',
        ], Response::HTTP_FORBIDDEN);
    }
}
