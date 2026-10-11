<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\UserPresenceService;
use App\Services\UserSessionService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackAuthenticatedUser
{
    public function __construct(
        private readonly UserPresenceService $presence,
        private readonly UserSessionService $sessions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($request->hasSession() && ! $this->sessions->isCurrent(
            $user,
            $request->session()->get(UserSessionService::VERSION_KEY),
        )) {
            return new JsonResponse([
                'message' => 'Phiên đăng nhập đã bị thu hồi. Vui lòng đăng nhập lại.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($request->hasSession()) {
            $this->sessions->register($user, $request->session()->getId());
        }

        $this->presence->heartbeat($user);

        return $next($request);
    }
}
