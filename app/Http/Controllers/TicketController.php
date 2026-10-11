<?php

namespace App\Http\Controllers;

use App\Actions\CreateTicket;
use App\Actions\RequestShopOwnerUpgrade;
use App\Enums\TicketTypeEnum;
use App\Http\Requests\StoreShopOwnerUpgradeRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    public function storeShopOwnerUpgrade(
        StoreShopOwnerUpgradeRequest $request,
        RequestShopOwnerUpgrade $requestShopOwnerUpgrade,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $ticket = $requestShopOwnerUpgrade->handle($user, $request->validated(), $request->headers->get('referer'));

        return response()->json([
            'message' => 'Yêu cầu nâng cấp Chủ Shop đã được gửi để quản trị viên xét duyệt.',
            'data' => $ticket,
        ], 201);
    }

    public function store(StoreTicketRequest $request, CreateTicket $createTicket): JsonResponse
    {
        $validated = $request->validated();
        $ticket = $createTicket->handle((string) $validated['user_id'], $validated, $request->headers->get('referer'));

        if ($ticket->type === TicketTypeEnum::LockAppeal && $request->hasSession()) {
            $request->session()->forget([
                'locked_ticket_user_id',
                'locked_ticket_verified_at',
            ]);
        }

        return response()->json([
            'message' => 'Ticket đã được ghi nhận.',
            'ticket_submitted' => true,
            'data' => $ticket,
        ], 201);
    }
}
