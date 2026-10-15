<?php

namespace App\Actions;

use App\Enums\ShopReactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\ChatConversation;
use App\Models\ShopFollow;
use App\Models\ShopProduct;
use App\Models\ShopReaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class GetShopEngagementMetrics
{
    /** @return array<string, mixed> */
    public function handle(User $shop, ?User $viewer = null): array
    {
        $products = ShopProduct::query()->whereBelongsTo($shop, 'shopOwner');
        $totalProductsCount = (clone $products)->count();
        $activeProductsCount = (clone $products)->where('is_active', true)->count();

        $reactions = ShopReaction::query()->whereBelongsTo($shop, 'shopOwner');
        $likesCount = (clone $reactions)->where('reaction', ShopReactionTypeEnum::Like)->count();
        $dislikesCount = (clone $reactions)->where('reaction', ShopReactionTypeEnum::Dislike)->count();
        $reactionsCount = $likesCount + $dislikesCount;

        $customerConversations = ChatConversation::query()
            ->whereBelongsTo($shop, 'shopOwner')
            ->whereHas('messages', fn (Builder $query) => $query
                ->whereColumn('chat_messages.sender_id', 'chat_conversations.buyer_id'));
        $customerConversationCount = (clone $customerConversations)->count();
        $respondedConversationCount = (clone $customerConversations)
            ->whereHas('messages', fn (Builder $query) => $query
                ->whereColumn('chat_messages.sender_id', 'chat_conversations.shop_owner_id'))
            ->count();

        $viewerCanInteract = $viewer !== null
            && in_array($viewer->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER, UserRoleEnum::ADMINISTRATOR], true)
            && (bool) $viewer->is_active
            && $viewer->getKey() !== $shop->getKey();
        $viewerReaction = $viewerCanInteract
            ? ShopReaction::query()
                ->whereBelongsTo($viewer, 'customer')
                ->whereBelongsTo($shop, 'shopOwner')
                ->value('reaction')
            : null;

        return [
            'products_count' => $activeProductsCount,
            'total_products_count' => $totalProductsCount,
            'hidden_products_count' => $totalProductsCount - $activeProductsCount,
            'followers_count' => ShopFollow::query()
                ->whereBelongsTo($shop, 'shopOwner')
                ->count(),
            'likes_count' => $likesCount,
            'dislikes_count' => $dislikesCount,
            'reactions_count' => $reactionsCount,
            'satisfaction_rate' => $reactionsCount > 0
                ? (int) round(($likesCount / $reactionsCount) * 100)
                : null,
            'response_rate' => $customerConversationCount > 0
                ? (int) round(($respondedConversationCount / $customerConversationCount) * 100)
                : null,
            'customer_conversations_count' => $customerConversationCount,
            'responded_conversations_count' => $respondedConversationCount,
            'viewer' => [
                'can_interact' => $viewerCanInteract,
                'is_following' => $viewerCanInteract && ShopFollow::query()
                    ->whereBelongsTo($viewer, 'follower')
                    ->whereBelongsTo($shop, 'shopOwner')
                    ->exists(),
                'reaction' => $viewerReaction instanceof ShopReactionTypeEnum
                    ? $viewerReaction->value
                    : $viewerReaction,
            ],
        ];
    }
}
