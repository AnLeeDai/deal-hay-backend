# Deal Hay API contract

Base URL: `/api`  
Auth: `Authorization: Bearer <sanctum-token>` unless marked Public.  
All successful JSON responses use UTF-8 JSON. Validation failures return `422 { message, errors }`.

## Public catalog & lookup

| Method | Path | Auth | Request body / query | Response shape |
|---|---|---|---|---|
| GET | `/` | Public | `category?` | `{ filters, categories, featuredShops, products }` |
| GET | `/shops/{shopUserCode}` | Public | `category?`, `page?` | `{ shop, categories, products, metrics, viewerState }` |
| GET | `/shops/{shopUserCode}/products/{productSlug}` | Public | none | `{ shop, product, relatedProducts, reviews, reviewSummary }` |
| GET | `/addresses/provinces` | Public | none | `[{ code, name }]` |
| GET | `/addresses/provinces/{provinceCode}/wards` | Public | none | `[{ code, name, division_type }]` |
| GET | `/media/assets/{path}` | Public | none | `302` redirect to resolved asset URL |
| POST | `/media/browser/transform` | Public bearer upload token | multipart `image` | Binary WebP response for browser-compressed uploads |

## Authentication

| Method | Path | Auth | Request body | Response shape |
|---|---|---|---|---|
| POST | `/auth/register` | Public | `email`, `password`, `password_confirmation`, `accept_terms` | `201 { message, token, user }` |
| POST | `/auth/login` | Public | `email`, `password`, `admin_verification_code?` | `{ message, token, user }`; locked account returns `423 { message, lock_reason, user }` |
| POST | `/auth/forgot-password` | Public | `email` | `{ message }` |
| POST | `/auth/reset-password` | Public | `email`, `token`, `password`, `password_confirmation` | `{ message }` |
| GET | `/auth/me` | Bearer | none | `{ data: user, meta: { unread_messages_count, unread_notifications_count } }` |
| POST | `/auth/logout` | Bearer | none | `{ message }` |

## Tickets, notifications, presence

`GET /diagnostics` requires an active signed-in user and allows six requests per minute per account. It returns `{ checked_at, duration_ms, checks: { database, cache, storage, reverb } }`. Each check has `{ status: "ok" | "failed" | "skipped", latency_ms: number | null, message }`. Dependency failures remain HTTP 200 with a failed check; authentication and rate limiting use 401/403/429. Samples are cached for 15 seconds on the API host while responses use `Cache-Control: no-store`. Times measure application operations, including SQL or handshakes, and are not pure network ping. Only configured servers are probed; credentials, hostnames and internal errors are omitted.

| Method | Path | Auth | Request body | Response shape |
|---|---|---|---|---|
| POST | `/tickets` | Public for lock appeal, Bearer for notification appeal | `user_id`, `type`, `message`, `source?`, `notification_id?`, `notification_title?` | `201 { message, ticket_submitted, data }` |
| POST | `/shop-owner-upgrade-requests` | Bearer | `full_name`, `shop_name`, `email`, `shop_email`, `phone_number`, `shop_phone_number`, `province`, `ward`, `address_detail`, `message` | `201 { message, data }` |
| GET | `/notifications` | Bearer | `page?` | `{ notification_page }` paginator |
| PATCH | `/notifications/read-all` | Bearer | none | `{ message }` |
| PATCH | `/notifications/{notification}/read` | Bearer | none | `{ message }` |
| POST | `/presence/heartbeat` | Bearer | `conversation_id?` | `{ ok, expires_in, server_time }` |

## Chats

| Method | Path | Auth | Request body / query | Response shape |
|---|---|---|---|---|
| GET | `/messages` | Bearer | none | `{ conversations, selectedConversation }` |
| GET | `/messages/{conversationSlug}` | Bearer | `limit?` | `{ conversations, selectedConversation }` |
| GET | `/messages/{conversationSlug}/history` | Bearer | `cursor?`, `limit?` | `{ messages, has_more, next_cursor }` |
| POST | `/shops/{shopUserCode}/chat` | Bearer | `client_message_id`, `content?`, `product_id?`, `variant_id?` | `201/200 { conversation, replayed }` + `Idempotency-Replayed` |
| POST | `/messages/{conversationSlug}` | Bearer | multipart/json `client_message_id`, `body?`, `shop_product_id?`, `shop_product_variant_id?`, `attachment?` | `201/200 { id, sender, body, attachment, created_at, replayed }` |
| PATCH | `/messages/{conversationSlug}/read` | Bearer | none | `{ ok: true }` |
| GET | `/messages/attachments/{messageId}` | Bearer | none | Attachment stream with download headers |

## Shop engagement & reviews

| Method | Path | Auth | Request body | Response shape |
|---|---|---|---|---|
| POST | `/shops/{shopUserCode}/follow` | Bearer | none | `201 { message }` |
| DELETE | `/shops/{shopUserCode}/follow` | Bearer | none | `{ message }` |
| PUT | `/shops/{shopUserCode}/reaction` | Bearer | `reaction` | `{ message }` |
| DELETE | `/shops/{shopUserCode}/reaction` | Bearer | none | `{ message }` |
| PUT | `/shops/{shopUserCode}/products/{productSlug}/review` | Bearer | `reaction`, `content?`, `remove_image?`, `image?` | `{ message }` |
| DELETE | `/shops/{shopUserCode}/products/{productSlug}/review` | Bearer | none | `{ message }` |
| PATCH | `/shop/reviews/{reviewId}/reply` | Bearer shop owner | `reply` | `{ message, data }` |

## Profile

| Method | Path | Auth | Request body | Response shape |
|---|---|---|---|---|
| GET | `/private/profile/me` | Bearer | none | `{ auth: { user } }` |
| PATCH | `/private/profile/me` | Bearer | `full_name?`, `email?`, `phone_number?`, `province?`, `ward?`, `address_detail?`, `email_change_code?` | `{ message, data }` |
| PATCH | `/private/me/password` | Bearer | `current_password`, `password`, `password_confirmation` | `{ message }` |
| POST | `/private/me/avatar` | Bearer | multipart `avatar` | `{ message, data }` |

## Shop management

| Method | Path | Auth | Request body / query | Response shape |
|---|---|---|---|---|
| GET | `/private/shop/manage` | Bearer shop owner | none | Management payload from monolith actions: `{ shop, categories, products, coupons, orders, metrics, ... }` |
| PATCH | `/private/shop/manage` | Bearer shop owner | `shop_name?`, `shop_email?`, `shop_phone_number?`, `shop_province?`, `shop_ward?`, `shop_address_detail?`, `email_change_code?` | `{ message, data }` |
| POST | `/private/shop/avatar` | Bearer shop owner | multipart `avatar` | `{ message, data }` |
| POST | `/private/shop/products` | Bearer shop owner | Product fields from source requests, optional multipart `image`, `gallery_images[]`, staged upload tokens `primary_image_upload?`, `gallery_image_uploads[]?` | `201 { message, data }` |
| PATCH | `/private/shop/products/{productId}` | Bearer shop owner | Same product fields plus `remove_image_ids[]?` | `{ message, data }` |
| PATCH | `/private/shop/products/{productId}/status` | Bearer shop owner | `is_active` | `{ message, data }` |
| DELETE | `/private/shop/products/{productId}` | Bearer shop owner | none | `{ message }` |
| PATCH | `/private/shop/products/{productId}/inventory` | Bearer shop owner | `variant_id?`, `quantity_delta`, `note?` | `{ message, data }` |
| POST | `/private/shop/coupons` | Bearer shop owner | Coupon payload from source request (`code`, `discount_type`, `discount_value`, `minimum_order_value?`, `starts_at?`, `ends_at?`, `usage_limit?`, `is_active`) | `201 { message, data }` |
| PATCH | `/private/shop/coupons/{couponId}` | Bearer shop owner | Same coupon payload | `{ message, data }` |
| DELETE | `/private/shop/coupons/{couponId}` | Bearer shop owner | none | `{ message }` |
| POST | `/private/shop/orders` | Bearer shop owner | Order payload from source request including idempotency fields and items | `201/200 { message, replayed }` + `Idempotency-Replayed` |
| PATCH | `/private/shop/orders/{orderId}/status` | Bearer shop owner | `status` | `{ message, data }` |
| PATCH | `/private/shop/categories/reorder` | Bearer shop owner | `category_ids[]` | `{ message }` |

## Media & staged uploads

| Method | Path | Auth | Request body | Response shape |
|---|---|---|---|---|
| POST | `/private/media/transform` | Bearer | `size` (1 byte to 30 MiB, in bytes), `purpose` (`avatar`, `product`, `review`, `chat`) | `{ url, token }` for direct browser transform upload |
| POST | `/private/uploads/images/presign` | Bearer | Up to 51 `uploads: [{ size, content_type }]`; each image is 1 byte to 30 MiB | `{ uploads: [{ token, url?, headers? }] }` |
| POST | `/private/uploads/images` | Bearer | Up to 51 `uploads: [{ size, content_type }]`; each image is 1 byte to 30 MiB | `{ uploads: [{ token }] }` |
| POST | `/private/uploads/images/prepare` | Bearer | multipart `token`, `image` | `204` |
| DELETE | `/private/uploads/images/staged` | Bearer | `tokens[]` | `204` |

The frontend first obtains a transform grant using its authenticated Server Action, then sends the original image directly to `/media/browser/transform` with the short-lived grant as its bearer token. The existing transform returns WebP up to 20 KiB; only that result goes through the final Server Action. This keeps original images up to 30 MiB out of Vercel Function request bodies. `IMAGE_BROWSER_URL` (or its default, `APP_URL`) must be reachable from the browser, and backend CORS must allow the frontend origin and the `Authorization` header.

## Admin

| Method | Path | Auth | Request body / query | Response shape |
|---|---|---|---|---|
| GET | `/admin/users` | Bearer admin | `page?`, filters from source request | `{ users }` paginator |
| PATCH | `/admin/users/{userId}/toggle-lock` | Bearer admin | `action` (`lock`/`unlock`), `lock_reason?` | `{ message, data }` |
| GET | `/admin/shops` | Bearer admin | filters / pagination from source request | Admin shop listing payload |
| GET | `/admin/shops/{shopId}` | Bearer admin | filters / pagination | Admin shop detail payload |
| DELETE | `/admin/shops/{shopId}/products/{productId}` | Bearer admin | `reason` | `{ message }` |
| GET | `/admin/categories` | Bearer admin | none | `{ categories }` |
| POST | `/admin/categories` | Bearer admin | `name`, `is_active?` | `201 { message, data }` |
| PATCH | `/admin/categories/{categoryId}` | Bearer admin | `name`, `is_active?` | `{ message, data }` |
| DELETE | `/admin/categories/{categoryId}` | Bearer admin | none | `{ message }` |
| GET | `/admin/tickets` | Bearer admin | filters / pagination from source request | Admin ticket listing payload |
| GET | `/admin/tickets/{ticketId}` | Bearer admin | filters / pagination | Admin ticket detail payload |
| PATCH | `/admin/tickets/{ticketId}` | Bearer admin | `status`, `admin_note?` | `{ message, data }` |

## Error contract

| Status | Shape |
|---|---|
| 401 | `{ "message": "Unauthenticated." }` |
| 403 | `{ "message": "..." }` |
| 404 | `{ "message": "Not Found" }` or Laravel JSON not-found payload |
| 422 | `{ "message": "The given data was invalid.", "errors": { ... } }` |
| 423 | Locked-account login response |
| 500 | `{ "message": "Hệ thống chưa thể xử lý yêu cầu. Vui lòng thử lại sau." }` |
