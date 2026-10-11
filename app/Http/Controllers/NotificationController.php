<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'notification_page' => $this->notifications->forUser($user),
        ]);
    }

    public function read(Request $request, DatabaseNotification $notification): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->notifications->markAsRead($user, $notification);

        return response()->json(['message' => 'Đã đánh dấu đã đọc.']);
    }

    public function readAll(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->notifications->markAllAsRead($user);

        return response()->json(['message' => 'Đã đánh dấu toàn bộ thông báo là đã đọc.']);
    }
}
