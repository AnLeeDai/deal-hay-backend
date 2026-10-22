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
use Illuminate\Support\Facades\DB;

class GetShopEngagementMetrics
{
    /** @return array<string, mixed> */
    public function handle(User $shop, ?User $viewer = null): array
    {
        $viewerCanInteract = $viewer !== null
            && in_array($viewer->role, [UserRoleEnum::CUSTOMER, UserRoleEnum::SHOP_OWNER, UserRoleEnum::ADMINISTRATOR], true)
            && (bool) $viewer->is_active
            && $viewer->getKey() !== $shop->getKey();
        $products = ShopProduct::query()
            ->whereBelongsTo($shop, 'shopOwner')
            ->selectRaw('COUNT(*) as total_count, COALESCE(SUM(CASE WHEN is_active = ? THEN 1 ELSE 0 END), 0) as active_count', [true])
            ->first();
        $totalProductsCount = (int) $products->total_count;
        $activeProductsCount = (int) $products->active_count;

        $reactions = ShopReaction::query()
            ->whereBelongsTo($shop, 'shopOwner')
            ->selectRaw('COALESCE(SUM(CASE WHEN reaction = ? THEN 1 ELSE 0 END), 0) as likes_count', [ShopReactionTypeEnum::Like->value])
            ->selectRaw('COALESCE(SUM(CASE WHEN reaction = ? THEN 1 ELSE 0 END), 0) as dislikes_count', [ShopReactionTypeEnum::Dislike->value])
            ->when($viewerCanInteract, fn (Builder $query) => $query
                ->selectRaw('MAX(CASE WHEN customer_id = ? THEN reaction END) as viewer_reaction', [$viewer->getKey()]))
            ->first();
        $likesCount = (int) $reactions->likes_count;
        $dislikesCount = (int) $reactions->dislikes_count;
        $reactionsCount = $likesCount + $dislikesCount;

        $customerConversations = ChatConversation::query()
            ->select('id')
            ->whereBelongsTo($shop, 'shopOwner')
            ->whereHas('messages', fn (Builder $query) => $query
                ->whereColumn('chat_messages.sender_id', 'chat_conversations.buyer_id'))
            ->withExists(['messages as has_shop_reply' => fn (Builder $query) => $query
                ->whereColumn('chat_messages.sender_id', 'chat_conversations.shop_owner_id')]);
        $conversations = DB::query()
            ->fromSub($customerConversations, 'customer_conversations')
            ->selectRaw('COUNT(*) as customer_count, COALESCE(SUM(CASE WHEN has_shop_reply THEN 1 ELSE 0 END), 0) as responded_count')
            ->first();
        $customerConversationCount = (int) $conversations->customer_count;
        $respondedConversationCount = (int) $conversations->responded_count;

        $followers = ShopFollow::query()
            ->whereBelongsTo($shop, 'shopOwner')
            ->selectRaw('COUNT(*) as followers_count')
            ->when($viewerCanInteract, fn (Builder $query) => $query
                ->selectRaw('COALESCE(MAX(CASE WHEN follower_id = ? THEN 1 ELSE 0 END), 0) as viewer_is_following', [$viewer->getKey()]))
            ->first();

        return [
            'products_count' => $activeProductsCount,
            'total_products_count' => $totalProductsCount,
            'hidden_products_count' => $totalProductsCount - $activeProductsCount,
            'followers_count' => (int) $followers->followers_count,
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
                'is_following' => $viewerCanInteract && (bool) $followers->viewer_is_following,
                'reaction' => $viewerCanInteract ? $reactions->viewer_reaction : null,
            ],
        ];
    }
}
