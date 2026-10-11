<?php

namespace App\Actions;

use App\Models\ChatConversation;
use App\Models\User;
use App\Services\UserPresenceService;

class RecordPresenceHeartbeat
{
    public function __construct(private readonly UserPresenceService $presence) {}

    /** @return array<string, mixed> */
    public function handle(User $user, ?string $conversationId): array
    {
        $conversation = filled($conversationId)
            ? ChatConversation::query()->findOrFail($conversationId)
            : null;

        abort_if($conversation !== null && ! $conversation->includes($user), 403);

        if ($conversation === null) {
            $this->presence->clearViewing($user);
        }

        return $this->presence->heartbeat($user, $conversation);
    }
}
