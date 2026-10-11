<?php

use App\Enums\UserRoleEnum;
use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('admin-notifications', function ($user) {
    return $user->role === UserRoleEnum::ADMINISTRATOR;
});

Broadcast::channel('user-notifications.{id}', function ($user, string $id) {
    return (string) $user->id === $id;
});

Broadcast::channel('chat.{conversationId}', function (User $user, string $conversationId) {
    $conversation = ChatConversation::query()->find($conversationId);

    return $conversation !== null
        && (bool) $user->is_active
        && $conversation->includes($user);
});

Broadcast::channel('chat-presence.{conversationId}', function (User $user, string $conversationId) {
    $conversation = ChatConversation::query()->find($conversationId);

    if ($conversation === null || ! (bool) $user->is_active || ! $conversation->includes($user)) {
        return false;
    }

    return [
        'id' => (string) $user->getKey(),
        'name' => (string) $user->full_name,
    ];
});

Broadcast::channel('user-chat.{id}', function (User $user, string $id) {
    return (bool) $user->is_active && (string) $user->id === $id;
});
