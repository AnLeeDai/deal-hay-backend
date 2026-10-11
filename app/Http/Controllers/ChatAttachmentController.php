<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowChatAttachmentRequest;
use App\Models\ChatMessage;
use App\Services\ChatAttachmentService;
use Symfony\Component\HttpFoundation\Response;

class ChatAttachmentController extends Controller
{
    public function __construct(
        private readonly ChatAttachmentService $attachments,
    ) {}

    public function __invoke(
        ShowChatAttachmentRequest $request,
        ChatMessage $message,
    ): Response {
        $download = $this->attachments->download($message);

        return response()->stream(
            $download['callback'],
            Response::HTTP_OK,
            $download['headers'],
        );
    }
}
