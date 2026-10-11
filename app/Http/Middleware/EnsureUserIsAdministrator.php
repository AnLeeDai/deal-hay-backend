<?php

namespace App\Http\Middleware;

use App\Enums\UserRoleEnum;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdministrator
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== UserRoleEnum::ADMINISTRATOR) {
            return new JsonResponse([
                'message' => 'Bạn không có quyền truy cập tài nguyên này.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
