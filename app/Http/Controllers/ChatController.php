<?php

namespace App\Http\Controllers;

use App\Actions\GetChatPage;
use App\Actions\MarkChatConversationRead;
use App\Actions\StartShopChat;
use App\Actions\StoreChatMessage;
use App\Http\Requests\ListChatsRequest;
use App\Http\Requests\MarkChatConversationReadRequest;
use App\Http\Requests\ShowChatRequest;
use App\Http\Requests\StartShopChatRequest;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\ChatConversation;
use App\Models\User;
use App\Services\ChatPayloadService;
use Illuminate\Http\JsonResponse;

class ChatController extends Controller
{
    public function __construct(
        private readonly GetChatPage $getChatPage,
        private readonly MarkChatConversationRead $markConversationRead,
        private readonly StartShopChat $startShopChat,
        private readonly StoreChatMessage $storeChatMessage,
        private readonly ChatPayloadService $payloads,
    ) {}

    public function index(ListChatsRequest $request): JsonResponse
    {
        return response()->json($this->getChatPage->handle($request->user()));
    }

    public function show(ShowChatRequest $request, ChatConversation $conversation): JsonResponse
    {
        $this->markConversationRead->handle($conversation, $request->user());

        return response()->json($this->getChatPage->handle(
            $request->user(),
            $conversation,
            $request->validated('limit'),
        ));
    }

    public function history(ShowChatRequest $request, ChatConversation $conversation): JsonResponse
    {
        return response()->json($this->getChatPage->olderMessages(
            $request->user(),
            $conversation,
            $request->validated('cursor'),
            $request->validated('limit'),
        ));
    }

    public function start(StartShopChatRequest $request, User $shop): JsonResponse
    {
        $result = $this->startShopChat->handle($request->user(), $shop, $request->validated());

        return response()->json([
            'conversation' => $this->payloads->conversation($result['conversation'], $request->user()),
            'replayed' => $result['replayed'],
        ], $result['replayed'] ? 200 : 201)->header('Idempotency-Replayed', $result['replayed'] ? 'true' : 'false');
    }

    public function storeMessage(StoreChatMessageRequest $request, ChatConversation $conversation): JsonResponse
    {
        $result = $this->storeChatMessage->handle(
            $request->user(),
            $conversation,
            $request->validated(),
            $request->file('attachment'),
        );

        return response()->json([
            ...$this->payloads->message($result['message']),
            'replayed' => $result['replayed'],
        ], $result['replayed'] ? 200 : 201)->header('Idempotency-Replayed', $result['replayed'] ? 'true' : 'false');
    }

    public function markRead(MarkChatConversationReadRequest $request, ChatConversation $conversation): JsonResponse
    {
        $this->markConversationRead->handle($conversation, $request->user());

        return response()->json(['ok' => true]);
    }
}
