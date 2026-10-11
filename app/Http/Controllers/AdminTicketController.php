<?php

namespace App\Http\Controllers;

use App\Actions\GetAdminTicketPage;
use App\Actions\ReviewTicket;
use App\Http\Requests\AdminUpdateTicketRequest;
use App\Http\Requests\ListAdminTicketsRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminTicketController extends Controller
{
    public function __construct(
        private readonly GetAdminTicketPage $ticketPage,
        private readonly ReviewTicket $reviewTicket,
    ) {}

    public function index(ListAdminTicketsRequest $request): JsonResponse
    {
        return response()->json($this->ticketPage->handle($request->validated()));
    }

    public function show(ListAdminTicketsRequest $request, Ticket $ticket): JsonResponse
    {
        return response()->json($this->ticketPage->handle($request->validated(), $ticket));
    }

    public function update(AdminUpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $this->reviewTicket->handle($ticket, $admin, $request->validated());

        return response()->json([
            'message' => 'Ticket đã được xử lý.',
            'data' => $ticket->refresh(),
        ]);
    }
}
