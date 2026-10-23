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

## Authentication

| Method | Path | Auth | Request body | Response shape |
|---|---|---|---|---|
| POST | `/auth/register` | Public | `email`, `password`, `password_confirmation`, `accept_terms` | `201 { message, token, user }` |
| POST | `/auth/login` | Public | `email`, `password`, `admin_verification_code?` | `{ message, token, user }`; locked account returns `423 { message, lock_reason, user }` |
| POST | `/auth/forgot-password` | Public | `email` | `{ message }` |
| POST | `/auth/reset-password` | Public | `email`, `token`, `password`, `password_confirmation` | `{ message }` |
| GET | `/auth/me` | Bearer | none | `{ data: user, meta: { unread_messages_count, unread_notifications_count } }` |
| POST | `/auth/logout` | Bearer | none | `{ message }` |

## Network speedtest

| Method | Path | Auth | Request | Response |
|---|---|---|---|---|
| POST | `/diagnostics/speedtest/session` | Active signed-in user; 3 starts/minute | none | `{ token, expires_at, endpoint_path: "/diagnostics/speedtest", limits: { max_download_bytes, max_upload_bytes, max_session_bytes, max_requests } }` |
| GET | `/diagnostics/speedtest/ping` | Dedicated speedtest Bearer token | none | `204`, no body, `Cache-Control: no-store` |
| GET | `/diagnostics/speedtest/download` | Dedicated speedtest Bearer token | Preferred `X-Speedtest-Bytes` request header; fallback `bytes?` query, integer 1–4194304, default 1048576 | Exact-length random `application/octet-stream`, `Content-Encoding: identity`, `X-Speedtest-Bytes`, `Vary: X-Speedtest-Bytes` |
| POST | `/diagnostics/speedtest/upload` | Dedicated speedtest Bearer token | Raw `application/octet-stream`, maximum 2097152 bytes | `{ received_bytes }`, matching `X-Speedtest-Bytes`; payload is discarded |

Grants expire after 120 seconds and permit at most 64 requests and 64 MiB reserved transfer bytes. Each upload reserves its maximum 2 MiB before reading, including missing or false Content-Length. Grants are independent of account tokens and only accepted on the three probe routes. Missing, invalid and expired grants return 401; size errors use 413/422, unsupported upload MIME 415, inconsistent Content-Length 400, exhausted byte/request budgets 429. Probe limiters additionally allow 120 requests/minute/token and 240/minute/IP. Metadata follows the configured cache limiter store (or default cache); grant counters update atomically without extending expiration. Payloads are not saved to DB/R2. The probes do not execute dependency diagnostics or accept remote target URLs.

The browser combines the trusted API base URL with `endpoint_path`, then probes that host directly with credentials omitted. Probe URLs stay fixed across samples, and adaptive downloads send `X-Speedtest-Bytes` so changing sample size can reuse the warmed CORS preflight. This header takes precedence over the query even when invalid. Configured CORS origins may send Authorization, Content-Type and X-Speedtest-Bytes and read X-Speedtest-Bytes, Content-Encoding and Retry-After; Timing-Allow-Origin is sent for configured frontend origins. HTTP ping measures the complete browser/API request round trip, including application work, separately from the server-side health probes below.

## Tickets, notifications, presence

`GET /diagnostics` requires an active signed-in user and allows six requests per minute per account. It returns `{ checked_at, duration_ms, checks: { database, cache, storage, reverb } }`. Each check has `{ status: "ok" | "failed" | "skipped", latency_ms: number | null, message }`. Dependency failures remain HTTP 200 with a failed check; authentication and rate limiting use 401/403/429. Samples are cached for 15 seconds on the API host while responses use `Cache-Control: no-store`. Times measure application operations, including SQL or handshakes, and are not pure network ping. Only configured servers are probed; credentials, hostnames and internal errors are omitted.

Successful responses also include fresh `request: { duration_ms, database_queries, database_ms, probes_cached, limiter_store }` and `Server-Timing: app;dur=..., db;dur=...`. This request timing includes authentication, throttling and controller work measured inside Laravel middleware. The SQL count covers completed framework statements, excluding driver-internal initialization statements, transaction begin/commit and failed queries. SQL elapsed time comes from query events; the first query can include opening and initializing the connection. PHP/bootstrap startup, waiting for a PHP thread, proxy and network time between Next.js and the API are outside the request measurement. The top-level `duration_ms` remains the latest dependency sample's duration, including when `probes_cached` is true; it must not be treated as the current request's elapsed time.

| Method | Path | Auth | Request body | Response shape |
|---|---|---|---|---|
| POST | `/tickets` | Public for lock appeal, Bearer for notification appeal | `user_id`, `type`, `message`, `source?`, `notification_id?`, `notification_title?` | `201 { message, ticket_submitted, data }` |
| POST | `/shop-owner-upgrade-requests` | Bearer | `full_name`, `shop_name`, `email`, `shop_email`, `phone_number`, `shop_phone_number`, `province`, `ward`, `address_detail`, `message` | `201 { message, data }` |
| GET | `/notifications` | Bearer | `page?` | `{ notification_page }` paginator |
| PATCH | `/notifications/read-all` | Bearer | none | `{ message }` |
| PATCH | `/notifications/read-visible` | Bearer | `notification_ids`: up to 50 UUIDs | `{ message }`; only owned unread notifications change; private `notifications.read` event synchronizes tabs |
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
| POST | `/private/shop/coupons` | Bearer shop owner | `name`, `product_ids` (required owned products), `discount_type`, `discount_value`, `minimum_order_amount?`, `maximum_discount_amount?`, `starts_at?`, `ends_at?`, `usage_limit?`, `is_active?` | `201 { message, data }`; code is system-generated |
| PATCH | `/private/shop/coupons/{couponId}` | Bearer shop owner | Same coupon payload; code is immutable | `{ message, data }` |
| DELETE | `/private/shop/coupons/{couponId}` | Bearer shop owner | none | `{ message }` |
| GET | `/shops/{shopCode}/coupons` | Public | `product_id?` | `{ coupons }`; only available offers assigned to active products |
| GET | `/private/shop/stocktakes` | Bearer shop owner | none | `{ data: StocktakeSummary[] }`, latest 30 owned sessions |
| GET | `/private/shop/inventory-units` | Bearer shop owner | `page?`, `per_page?` (1–200, default100), `search?`, `product_id?` | `{ data: InventoryUnit[], meta: { current_page, last_page, total, per_page } }`; available units with stable UUID/SKU; `code` equals the unit SKU and is the complete QR payload; legacy stock is backfilled without duplicating units |
| POST | `/private/shop/stocktakes` | Bearer shop owner | `name` (max120) | `201 { data: Stocktake }`, `counting_mode: serialized`; snapshot of each expected physical unit and aggregate stock/movement baseline |
| GET | `/private/shop/stocktakes/{id}` | Bearer shop owner | none | `{ data: Stocktake }` with quantity/financial summaries, current sale prices, confirmation token; each item includes first100 `units` plus `units_total` |
| GET | `/private/shop/stocktakes/{id}/items/{itemId}/units` | Bearer shop owner | `page?`, `per_page?` (1–100) | `{ data: StocktakeUnit[], meta }`; owner/session/item scoped; sorted by unit SKU |
| POST | `/private/shop/stocktakes/{id}/scan` | Bearer shop owner | `code` (unique unit SKU; older `DH:U:{uuid}` tags remain accepted), `quantity?` (must1), `scan_id` (UUID) | `{ data: Stocktake }`; case-insensitive lookup and scanner CR/LF/Tab boundary trimming; embedded controls rejected; physical-unit dedup across SKU/legacy aliases/independent scans and request retries; aggregate catalog codes rejected in serialized mode; unknown codes excluded from monetary valuation |
| PATCH | `/private/shop/stocktakes/{id}/units/{unitId}` | Bearer shop owner | `counted: false` | `{ data: Stocktake }`; undo count for one unit; rescan to count again |
| PATCH | `/private/shop/stocktakes/{id}/items/{itemId}` | Bearer shop owner | `counted_quantity` (0–999999) | Legacy sessions only; serialized sessions reject aggregate count edits |
| POST | `/private/shop/stocktakes/{id}/save` | Bearer shop owner | none | `{ message, data: Stocktake }`; checkpoint `saved_at` and unit prices, remains editable |
| POST | `/private/shop/stocktakes/{id}/complete` | Bearer shop owner | `adjust_stock` boolean, `confirmed: true`, `confirmation_token` from latest serialized report | `{ message, data: Stocktake }`; freezes current sale prices, financial summary and confirmer/time. False confirms report while retaining stock; true removes/restores exact unit IDs if stock/movements/available IDs are unchanged. Stale count/price token rejected; exact same request may be replayed safely |

Serialized money fields are `expected_value`, `counted_value`, `shortage_value`, `surplus_value`, `net_difference_value` (surplus minus shortage), and `debt_amount` (gross shortage value, no automatic surplus offset). They appear in `financial_summary` and quantity `summary`. Each known item has `unit_price` and `values`; unknown items have null monetary fields. Unit prices use variant sale price when present, including zero, otherwise product sale price. Missing and unexpected unit identities remain separate even when quantities balance. Completed data is immutable. Historical sessions retain `counting_mode: legacy` and no invented financial summary.
| POST | `/private/shop/orders` | Bearer shop owner | Order payload from source request including idempotency fields and items | `201/200 { message, replayed }` + `Idempotency-Replayed` |
| PATCH | `/private/shop/orders/{orderId}/status` | Bearer shop owner | `status` | `{ message, data }` |
| PATCH | `/private/shop/categories/reorder` | Bearer shop owner | `category_ids[]` | `{ message }` |

## Media & staged uploads

| Method | Path | Auth | Request body | Response shape |
|---|---|---|---|---|
| POST | `/private/uploads/images/presign` | Bearer | Up to 51 `uploads: [{ size, content_type: "image/webp" }]`; each prepared product image is 1 byte to 512 KiB (524288 bytes) | `{ uploads: [{ token, url?, headers? }] }` |
| POST | `/private/uploads/images` | Bearer | Up to 51 `uploads: [{ size, content_type: "image/webp" }]`; each prepared product image is 1 byte to 512 KiB (524288 bytes) | `{ uploads: [{ token }] }` |
| POST | `/private/uploads/images/prepare` | Bearer | multipart `token`, `image` containing the final static product WebP, maximum 512 KiB | `204` after storage succeeds |
| DELETE | `/private/uploads/images/staged` | Bearer | `tokens[]` | `204` |

Every image ingress accepts only the final static WebP with limits specific to its purpose:

| Purpose | Maximum file size | Maximum long edge |
|---|---|---|
| Product primary/gallery, direct or staged | 512 KiB (524288 bytes) | 1280 px |
| Profile/shop avatar | 20 KiB (20480 bytes) | 640 px |
| Review/chat image | 20 KiB (20480 bytes) | 1280 px |

The browser accepts source images up to 30 MiB and performs decoding, orientation, flattening to one static frame, resizing and WebP compression locally. It may shrink further to fit the corresponding byte budget. Product images retain a larger byte budget to preserve product detail. Browser decoding is serialized and at most two prepared images upload concurrently. An unsupported browser format or encoder fails locally rather than sending the original image to the backend.

For product creation, the browser prepares the final WebP images before declaring a batch through `/private/uploads/images`, then sends one token and one image per request through the authenticated same-origin `/api/live/uploads/images/prepare` transport, which maps to Laravel `/private/uploads/images/prepare`. Retries reuse the token. The final product create request contains `primary_image_upload` and `gallery_image_uploads[]`, with no image binaries. Up to 50 gallery images plus one primary image are supported. The presigned R2 channel remains available and applies the same final-image contract. Staged tokens remain owner scoped, size bound and single use; quotas, concurrency guards and cleanup remain in effect.

The backend performs lightweight WebP header and byte-size validation, streams the exact prepared bytes into R2 and saves the media reference in the database. It does not decode, resize, rotate or re-encode images. Transform grants and server image-transform endpoints have been removed, along with ImageManager/Intervention and the Docker GD/EXIF extensions. The `images` queue retains its name for storage cleanup, with no image transformations. Existing relative R2 references and resolved asset URLs remain compatible; `/media/assets/{path}` continues to redirect without processing image bytes. Chat attachment access still checks conversation membership.

PHP development/production limits are `upload_max_filesize=3M`, `post_max_size=32M` and `max_file_uploads=51`, allowing a complete multipart product batch or a non-image chat attachment of up to 3 MiB. The normal frontend product flow still sends one prepared image per staging request.

Deploy frontend and backend together and rebuild the backend container. This change requires no database migration. Old clients submitting original or otherwise unprepared images receive `422 { message, errors }`.

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
