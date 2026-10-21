<?php

use App\Http\Controllers\AddressController;
use App\Http\Controllers\AdminProductCategoryController;
use App\Http\Controllers\AdminShopController;
use App\Http\Controllers\AdminTicketController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ChatAttachmentController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CustomerProductOrderController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MediaGatewayController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\ProductDiscountCodeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicShopController;
use App\Http\Controllers\RealtimeConfigController;
use App\Http\Controllers\ShippingOfferController;
use App\Http\Controllers\ShippingQuoteController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\ShopCouponController;
use App\Http\Controllers\ShopFollowController;
use App\Http\Controllers\ShopInventoryController;
use App\Http\Controllers\ShopOrderController;
use App\Http\Controllers\ShopProductPricingController;
use App\Http\Controllers\ShopProductReviewController;
use App\Http\Controllers\ShopReactionController;
use App\Http\Controllers\ShopShippingSettingController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/media/assets/{path}', [MediaGatewayController::class, 'asset'])
    ->where('path', '.*')
    ->name('media.asset');
Route::post('/media/browser/transform', [MediaGatewayController::class, 'browserTransform'])->name('media.browser.transform');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/shops/{shop:user_code}', [PublicShopController::class, 'show'])->name('shops.show');
Route::get('/shops/{shop:user_code}/products/{shopProduct:slug}', [PublicShopController::class, 'showProduct'])
    ->scopeBindings()
    ->name('shops.products.show');
Route::post('/shops/{shop:user_code}/products/{shopProduct:slug}/shipping-quote', ShippingQuoteController::class)
    ->scopeBindings()->middleware('throttle:60,1')->name('shops.products.shipping-quote');
Route::get('/shops/{shop:user_code}/products/{shopProduct:slug}/shipping-offer', [ShippingOfferController::class, 'show'])
    ->scopeBindings()->middleware('throttle:60,1')->name('shops.products.shipping-offer.show');

Route::prefix('auth')->name('api.auth.')->group(function (): void {
    Route::post('/login', [UserController::class, 'signIn'])->middleware('throttle:6,1')->name('login');
    Route::post('/register', [UserController::class, 'signUp'])->middleware('throttle:5,10')->name('register');
    Route::post('/forgot-password', [UserController::class, 'forgotPassword'])->middleware('throttle:5,1')->name('forgot-password');
    Route::post('/reset-password', [UserController::class, 'resetPassword'])->middleware('throttle:5,1')->name('reset-password');
});

Route::post('/tickets', [TicketController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('tickets.store');

Route::prefix('addresses')->name('addresses.')->group(function (): void {
    Route::get('/provinces', [AddressController::class, 'provinces'])->name('provinces');
    Route::get('/provinces/{provinceCode}/wards', [AddressController::class, 'wards'])->name('wards');
});

Route::middleware(['auth:sanctum', 'active'])->group(function (): void {
    Route::get('/shops/{shop:user_code}/products/{shopProduct:slug}/shipping-offer/code', [ShippingOfferController::class, 'code'])
        ->scopeBindings()->middleware('throttle:60,1')->name('shops.products.shipping-offer.code');
    Route::post('/shops/{shop:user_code}/products/{shopProduct:slug}/shipping-offer/views', [ShippingOfferController::class, 'start'])
        ->scopeBindings()->middleware('throttle:30,1')->name('shops.products.shipping-offer.start');
    Route::post('/shops/{shop:user_code}/products/{shopProduct:slug}/shipping-offer/views/{shippingRewardView}/redeem', [ShippingOfferController::class, 'redeem'])
        ->scopeBindings()->middleware('throttle:30,1')->name('shops.products.shipping-offer.redeem');
    Route::get('/shop/shipping-settings', [ShopShippingSettingController::class, 'show'])->name('shop.shipping-settings.show');
    Route::put('/shop/shipping-settings', [ShopShippingSettingController::class, 'update'])->name('shop.shipping-settings.update');
    Route::get('/auth/me', [UserController::class, 'me'])->name('api.auth.me');
    Route::post('/auth/logout', [UserController::class, 'logout'])->name('api.auth.logout');
    Route::get('/discount-codes', [ProductDiscountCodeController::class, 'index'])->name('discount-codes.index');
    Route::post('/discount-codes/{productDiscountCode}/save', [ProductDiscountCodeController::class, 'save'])
        ->middleware('throttle:30,1')->name('discount-codes.save');

    Route::post('/shop-owner-upgrade-requests', [TicketController::class, 'storeShopOwnerUpgrade'])
        ->middleware('throttle:3,10')
        ->name('shop-owner-upgrade-requests.store');

    Route::get('/messages', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/messages/attachments/{message}', ChatAttachmentController::class)->name('chats.attachments.show');
    Route::get('/messages/{conversation:slug}', [ChatController::class, 'show'])->name('chats.show');
    Route::get('/messages/{conversation:slug}/history', [ChatController::class, 'history'])->name('chats.messages.history');
    Route::post('/shops/{shop:user_code}/chat', [ChatController::class, 'start'])
        ->middleware('throttle:start-chat')
        ->name('chats.start');
    Route::post('/messages/{conversation:slug}', [ChatController::class, 'storeMessage'])
        ->middleware('throttle:30,1')
        ->name('chats.messages.store');
    Route::patch('/messages/{conversation:slug}/read', [ChatController::class, 'markRead'])->name('chats.read');

    Route::post('/shops/{shop:user_code}/follow', [ShopFollowController::class, 'store'])->name('shops.follow.store');
    Route::delete('/shops/{shop:user_code}/follow', [ShopFollowController::class, 'destroy'])->name('shops.follow.destroy');
    Route::put('/shops/{shop:user_code}/reaction', [ShopReactionController::class, 'update'])->name('shops.reaction.update');
    Route::delete('/shops/{shop:user_code}/reaction', [ShopReactionController::class, 'destroy'])->name('shops.reaction.destroy');
    Route::post('/shops/{shop:user_code}/products/{shopProduct:slug}/orders', [CustomerProductOrderController::class, 'store'])
        ->scopeBindings()->middleware('throttle:10,1')->name('shops.products.orders.store');
    Route::put('/shops/{shop:user_code}/products/{shopProduct:slug}/review', [ShopProductReviewController::class, 'update'])
        ->scopeBindings()
        ->middleware('throttle:10,1')
        ->name('shops.products.review.update');
    Route::delete('/shops/{shop:user_code}/products/{shopProduct:slug}/review', [ShopProductReviewController::class, 'destroy'])
        ->scopeBindings()
        ->middleware('throttle:10,1')
        ->name('shops.products.review.destroy');
    Route::patch('/shop/reviews/{shopProductReview}/reply', [ShopProductReviewController::class, 'reply'])
        ->middleware('throttle:20,1')
        ->name('shop.reviews.reply');

    Route::post('/presence/heartbeat', PresenceController::class)
        ->middleware('throttle:presence-heartbeat')
        ->name('presence.heartbeat');
    Route::get('/realtime/config', RealtimeConfigController::class)->name('realtime.config');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/chat', [AdminUserController::class, 'startChat'])
            ->middleware('throttle:start-chat')->name('users.chat.store');
        Route::patch('/users/{user}/toggle-lock', [AdminUserController::class, 'toggleLock'])->name('users.toggle-lock');
        Route::get('/shops', [AdminShopController::class, 'index'])->name('shops.index');
        Route::get('/shops/{shop}', [AdminShopController::class, 'show'])->name('shops.show');
        Route::delete('/shops/{shop}/products/{shopProduct}', [AdminShopController::class, 'destroyProduct'])->name('shops.products.destroy');
        Route::get('/categories', [AdminProductCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminProductCategoryController::class, 'store'])->name('categories.store');
        Route::patch('/categories/{productCategory}', [AdminProductCategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{productCategory}', [AdminProductCategoryController::class, 'destroy'])->name('categories.destroy');
        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('tickets.index');
        Route::get('/tickets/{ticket}', [AdminTicketController::class, 'show'])->name('tickets.show');
        Route::patch('/tickets/{ticket}', [AdminTicketController::class, 'update'])->name('tickets.update');
    });

    Route::prefix('private')->group(function (): void {
        Route::get('/profile/me', [ProfileController::class, 'me'])->name('profile.me');
        Route::patch('/profile/me', [ProfileController::class, 'update'])
            ->middleware('throttle:email-change')
            ->name('profile.me.update');
        Route::patch('/me/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::post('/me/avatar', [MediaGatewayController::class, 'updateProfileAvatar'])->name('profile.avatar.update');

        Route::get('/shop/manage', [ShopController::class, 'manage'])->name('shop.manage');
        Route::patch('/shop/manage', [ShopController::class, 'update'])
            ->middleware('throttle:email-change')
            ->name('shop.update');
        Route::post('/shop/avatar', [MediaGatewayController::class, 'updateShopAvatar'])->name('shop.avatar.update');
        Route::post('/shop/products', [ShopController::class, 'storeProduct'])
            ->middleware('throttle:product-write')
            ->name('shop.products.store');
        Route::patch('/shop/products/{shopProduct}', [ShopController::class, 'updateProduct'])
            ->middleware('throttle:product-write')
            ->name('shop.products.update');
        Route::patch('/shop/products/{shopProduct}/status', [ShopController::class, 'updateProductStatus'])->name('shop.products.status.update');
        Route::patch('/shop/products/{shopProduct}/pricing', [ShopProductPricingController::class, 'update'])
            ->middleware('throttle:product-write')->name('shop.products.pricing.update');
        Route::delete('/shop/products/{shopProduct}', [ShopController::class, 'destroyProduct'])->name('shop.products.destroy');
        Route::patch('/shop/products/{shopProduct}/inventory', [ShopInventoryController::class, 'adjust'])->name('shop.products.inventory.adjust');
        Route::post('/shop/coupons', [ShopCouponController::class, 'store'])->name('shop.coupons.store');
        Route::patch('/shop/coupons/{shopCoupon}', [ShopCouponController::class, 'update'])->name('shop.coupons.update');
        Route::delete('/shop/coupons/{shopCoupon}', [ShopCouponController::class, 'destroy'])->name('shop.coupons.destroy');
        Route::post('/shop/orders', [ShopOrderController::class, 'store'])->name('shop.orders.store');
        Route::patch('/shop/orders/{shopOrder}/status', [ShopOrderController::class, 'updateStatus'])->name('shop.orders.status.update');
        Route::patch('/shop/categories/reorder', [ShopController::class, 'reorderCategories'])->name('shop.categories.reorder');

        Route::post('/media/transform', [MediaGatewayController::class, 'transform'])->name('media.transform');
        Route::post('/uploads/images/presign', [MediaGatewayController::class, 'storeStaged'])
            ->middleware('throttle:image-upload-presign')
            ->name('uploads.images.presign');
        Route::post('/uploads/images', [MediaGatewayController::class, 'storeUploadSessions'])
            ->middleware('throttle:image-upload-presign')
            ->name('uploads.images.store');
        Route::delete('/uploads/images/staged', [MediaGatewayController::class, 'discardStaged'])
            ->middleware('throttle:60,1')
            ->name('uploads.images.discard');
        Route::post('/uploads/images/prepare', [MediaGatewayController::class, 'prepareStaged'])
            ->middleware('throttle:180,1,image-upload-prepare')
            ->name('uploads.images.prepare');
    });
});
