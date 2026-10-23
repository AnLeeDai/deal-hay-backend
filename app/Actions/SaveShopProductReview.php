<?php

namespace App\Actions;

use App\Enums\ImagePurposeEnum;
use App\Models\ShopProduct;
use App\Models\ShopProductReview;
use App\Models\User;
use App\Services\MediaClient;
use App\Services\ShopProductReviewEligibility;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class SaveShopProductReview
{
    public function __construct(
        private readonly MediaClient $images,
        private readonly MediaClient $cleanup,
        private readonly ShopProductReviewEligibility $eligibility,
    ) {}

    /** @param array<string, mixed> $validated */
    public function handle(User $customer, ShopProduct $product, array $validated, ?UploadedFile $image): void
    {
        abort_unless(
            $this->eligibility->canReview($customer, $product),
            403,
            'Bạn cần đặt sản phẩm này trước khi đánh giá.',
        );

        $review = ShopProductReview::query()->firstOrNew([
            'customer_id' => $customer->getKey(),
            'shop_product_id' => $product->getKey(),
        ]);
        $previousImage = $review->getRawOriginal('image_url');
        $data = Arr::only($validated, ['reaction', 'content']);

        if ($image instanceof UploadedFile) {
            $data['image_url'] = $this->images->upload($image, $customer->user_code.'/product-reviews', purpose: ImagePurposeEnum::Review);
        } elseif ((bool) ($validated['remove_image'] ?? false)) {
            $data['image_url'] = null;
        }

        try {
            DB::transaction(function () use ($review, $data, $previousImage): void {
                $review->fill($data)->save();
                if ($previousImage !== null && $previousImage !== $review->getRawOriginal('image_url')) {
                    $this->cleanup->deleteLater([$previousImage]);
                }
            });
        } catch (Throwable $exception) {
            if (isset($data['image_url'])) {
                $this->cleanup->deleteLater([$data['image_url']]);
            }

            throw $exception;
        }

    }
}
