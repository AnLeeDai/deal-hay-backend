# Deal Hay Backend

API-only Laravel 13 backend for Deal Hay. It serves the Next.js frontend and includes Laravel Reverb, database queues, scheduled tasks, Resend mail, and Cloudflare R2 media storage.

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link
composer run dev
```

## Local defaults

- SQLite database: `database/database.sqlite`
- Sanctum bearer tokens only
- Mail: `log`
- Media disks: local `storage/app/r2` and `storage/app/r2-private`
- Frontend URL: `http://localhost:3000`

The local `.env.example` contains local defaults and placeholders for optional provider credentials. Add provider credentials only when switching a local service away from its local/log driver.

## Gửi email trên Render bằng Resend

File `.env` trên máy local không tự được đưa lên Render. Trong **Environment Groups → deal-hay-production**, điền đủ:

```env
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=no-reply@your-verified-domain.com
MAIL_FROM_NAME="Deal Hay"
RESEND_API_KEY=re_replace_with_your_real_api_key
```

Thay địa chỉ và API key ví dụ bằng giá trị thật. `MAIL_FROM_ADDRESS` phải thuộc domain đã xác minh trong Resend. `onboarding@resend.dev` chỉ gửi thử tới email đăng ký tài khoản Resend; gửi tới người dùng khác cần domain riêng đã xác minh. Xem [giới hạn domain thử nghiệm của Resend](https://resend.com/docs/knowledge-base/403-error-resend-dev-domain).

Liên kết nhóm với API và worker gửi mail. Trong **Environment** của từng service, xóa các biến `MAIL_MAILER`, `MAIL_FROM_ADDRESS`, `RESEND_API_KEY` trống hoặc sai nếu chúng đang ghi đè nhóm. Chọn **Save and deploy** sau khi sửa; **Save only** chưa áp dụng cấu hình vào tiến trình đang chạy. Entrypoint của dự án chạy `php artisan optimize` khi khởi động nên cấu hình được cache lại theo env mới. Xem [cấu hình biến môi trường Render](https://render.com/docs/configure-environment-variables).

Thiếu `MAIL_MAILER` sẽ dùng mặc định `log`: API có thể báo đã gửi nhưng email chỉ được ghi vào log. `MAIL_MAILER=resend` sử dụng HTTPS API; không cần `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`. Render Free chặn các cổng SMTP 25, 465 và 587, nên dùng Resend API cho cấu hình này. Xem [giới hạn Render Free](https://render.com/docs/free).

Mã đăng nhập admin, mã đổi email và liên kết đặt lại mật khẩu được gửi trực tiếp. Email thông báo đổi mật khẩu thành công và khóa/mở khóa tài khoản vẫn cần worker xử lý queue `default`; trên Render Free, dùng `DEPLOY_ROLE=web-worker` và `QUEUE_NAMES=images,default` cho API như hướng dẫn bên dưới. Nếu mail vẫn lỗi sau khi deploy, kiểm tra log API/worker và Resend Dashboard để phân biệt API key không hợp lệ, domain chưa xác minh hay lỗi giao thư.

## Dùng một bucket R2 cho ảnh và tin nhắn

Ảnh sản phẩm, avatar và ảnh/tệp trong tin nhắn đều dùng bucket `R2_BUCKET=deal-hay-bucket`. Disk `r2_private` là tên nội bộ của luồng chat, dùng cùng bucket và cặp khóa R2 với disk `r2`; không cần tạo bucket thứ hai. Ảnh chat lưu dưới `chat-media/{conversationId}/...` và được hiển thị qua API kiểm tra thành viên cuộc trò chuyện.

Trên Render, dùng các biến bên dưới. `R2_PRIVATE_BUCKET` đã được bỏ khỏi cấu hình; nếu còn giá trị cũ trên Dashboard thì có thể xóa, ứng dụng sẽ không dùng biến đó nữa. Sau khi deploy backend mới, cả hai disk đều đọc `R2_BUCKET`. Token R2 cần quyền **Object Read & Write** trên bucket này. Xem [cấu hình quyền S3 của Cloudflare R2](https://developers.cloudflare.com/r2/get-started/s3/).

```env
R2_DRIVER=s3
CHAT_MEDIA_DISK=r2_private
R2_BUCKET=deal-hay-bucket
R2_ACCESS_KEY_ID=replace_with_access_key_id
R2_SECRET_ACCESS_KEY=replace_with_secret_access_key
R2_ENDPOINT=https://your-account-id.r2.cloudflarestorage.com
```

Thay cặp khóa và account ID ví dụ bằng cấu hình thật. Khi chạy local, dùng `php artisan config:clear` sau khi đổi env; entrypoint trên Render tạo lại config cache khi service khởi động.

API tải ảnh vẫn kiểm tra thành viên cuộc trò chuyện. Tuy nhiên, nếu toàn bucket được bật public qua `r2.dev` hoặc custom domain thì URL R2 trực tiếp của ảnh chat cũng có thể truy cập được; tên disk `r2_private` không chặn đường truy cập công khai đó. Xem [public buckets của Cloudflare](https://developers.cloudflare.com/r2/buckets/public-buckets/). Lệnh cũ `app:migrate-chat-attachments-to-private` dùng cho hai bucket tách biệt và sẽ từ chối chạy khi hai disk cùng bucket.

## Trả lời tin nhắn và emoji

Bản cập nhật reply cần migration `2026_10_15_112011_add_reply_to_id_to_chat_messages_table.php`. Chạy `php artisan migrate --force` trước khi phục vụ backend mới. Với Render Free theo cấu hình bên dưới, giữ `RUN_MIGRATIONS=true` ở riêng service API để entrypoint tự chạy migration khi deploy.

Tin gửi có thể kèm `reply_to_id` của một tin trong cùng cuộc trò chuyện. API, lịch sử và sự kiện realtime trả cùng phần trích dẫn `reply_to`; tin cũ vẫn hiển thị bình thường. Nếu tin gốc bị xóa, nội dung tin trả lời vẫn được giữ.

Emoji là ký tự Unicode, hiển thị bằng bộ emoji của hệ điều hành. Frontend có bảng chọn nhanh và vẫn nhận emoji nhập từ bàn phím hệ thống: [Windows + .](https://support.microsoft.com/en-us/windows/keyboard-shortcuts-in-windows-dcc61a57-8ff0-cffe-9796-cb9706c75eec), [Control + Command + Space trên macOS](https://support.apple.com/en-us/102650), hoặc phím emoji trên điện thoại.

## Quảng cáo Google

Frontend dùng Google AdSense với publisher ID được cấp và Google Ad Manager Rewarded Ads cho ưu đãi vận chuyển. Script AdSense, metadata xác minh và `ads.txt` thuộc frontend; backend quản lý ad unit path, phiên nhận thưởng và mã vận chuyển. Backend không xác minh doanh thu hoặc lượt nhấp quảng cáo. Tích hợp Adsterra trước đây đã được gỡ khỏi frontend.

Đã gỡ adapter AdGem, các endpoint `rewarded-tasks` và `/api/postbacks/adgem/v3`, cùng cấu hình `REWARDED_TASK_*` và `ADGEM_*`. Các đường dẫn cũ trả 404; biến môi trường cũ không kích hoạt lại được hệ thống nhiệm vụ. Khi triển khai, khởi động lại API/worker để tải mã và route cache mới.

Giữ nguyên các bảng lịch sử quảng cáo và chuyển đổi để mã giảm giá đã lưu còn được kiểm tra và sử dụng theo điều kiện ban đầu. Luồng mã vận chuyển bên dưới dùng bảng riêng, không kích hoạt lại endpoint quảng cáo cũ.

## Phí vận chuyển Hà Nội

Chạy `php artisan migrate --force` để thêm `shop_shipping_settings` và các cột vận chuyển của đơn hàng. Không có migration xóa dữ liệu; đơn cũ có phí vận chuyển trong hệ thống bằng 0. Chủ shop cấu hình phí trong tab Cửa hàng trên frontend, mặc định tắt.

`GET/PUT /api/shop/shipping-settings` chỉ cho chủ shop đang hoạt động. Dữ liệu gồm `enabled`, `base_fee` và `ward_rates: [{ward_code, fee}]`; mức riêng thay thế phí mặc định, mỗi phường/xã chỉ có một mức và phải thuộc Hà Nội. Không tự đặt mức phí thay shop.

`POST /api/shops/{shopCode}/products/{slug}/shipping-quote` nhận `shipping_province_code` và `shipping_ward_code`, trả `shipping_quote`. Phí chỉ xác định khi địa chỉ thuộc Hà Nội và shop đã bật cấu hình; nếu chưa có báo phí, `fee` là `null`.

Checkout yêu cầu `quoted_shipping_fee` khớp phí hiện tại khi có báo phí. Server tự tính `shipping_fee`, `shipping_discount_amount`, `shipping_total` và tổng đơn; nếu phí đã thay đổi, trả lỗi tại `quoted_shipping_fee` để khách kiểm tra lại. Địa chỉ ngoài Hà Nội/shop chưa cấu hình giữ cách tính tiền hàng hiện tại.

### Ưu đãi mã miễn phí vận chuyển

Chạy `php artisan migrate --force` để thêm bảng mã vận chuyển và thông tin nhà cung cấp quảng cáo vào `shipping_reward_views`. Luồng dùng **Google Ad Manager Rewarded Ads**, mặc định **tắt** (`SHIPPING_REWARDS_ENABLED=false`). Tạo ad unit dành cho rewarded web trong tài khoản Ad Manager đã được duyệt, đặt `GOOGLE_AD_MANAGER_REWARDED_AD_UNIT_PATH` bằng đường dẫn đầy đủ như `/NETWORK_CODE/AD_UNIT_CODE`, sau đó đặt `SHIPPING_REWARDS_ENABLED=true` và nạp lại cấu hình. Mã publisher AdSense `ca-pub-…`, snippet AdSense và dòng `ads.txt` không thay thế được đường dẫn ad unit này; không dùng ad unit mẫu của Google để phát mã thật.

Khi bật, có đường dẫn ad unit hợp lệ và shop đã cấu hình vận chuyển, `GET /api/shops/{shopCode}/products/{slug}/shipping-offer` trả `available: true` cùng `rewarded_ad_unit_path`. Khi không khả dụng, đường dẫn trả `null`; cấu hình quảng cáo thiếu/sai có lý do `rewarded_ads_not_configured`. `POST .../shipping-offer/views` yêu cầu đăng nhập, tạo phiên 15 phút gắn với người dùng, sản phẩm, nhà cung cấp và ad unit hiện tại, chưa phát mã. Phiên cũ chưa có nhà cung cấp hoặc có ad unit khác cấu hình hiện tại không được nhận mã mới.

Frontend xin người dùng đồng ý xem quảng cáo và chỉ gọi `POST .../shipping-offer/views/{id}/redeem` với JSON `{"rewarded_slot_granted": true}` sau sự kiện GPT `rewardedSlotGranted` của đúng slot. Thiếu cờ, cờ khác boolean `true`, đóng quảng cáo trước khi nhận thưởng, không có quảng cáo hoặc lỗi tải đều không phát mã. Xem xong video hoặc nhấp quảng cáo không tự được coi là nhận thưởng; khách luôn có thể bỏ qua và đặt hàng với phí vận chuyển bình thường.

**Giới hạn xác thực:** GPT rewarded web báo sự kiện trong trình duyệt, không cung cấp chứng cứ server có chữ ký qua luồng tích hợp này. `rewarded_slot_granted` là khai báo từ client có thể bị giả mạo, không phải SSV hay xác minh do Google gửi về backend. Kiểm tra phiên/người dùng/sản phẩm/hạn và giới hạn request giúp bảo vệ luồng ứng dụng nhưng không chứng minh lượt xem ở phía Google. Nếu cần chống giả mạo bằng callback có chữ ký, phải dùng giải pháp có hỗ trợ xác minh server cho web trước khi cấp ưu đãi có giá trị. Tham khảo [mẫu GPT rewarded](https://developers.google.com/publisher-tag/samples/display-rewarded-ad), [sự kiện GPT](https://developers.google.com/publisher-tag/reference#googletag.events.RewardedSlotGrantedEvent) và [chính sách phần thưởng](https://support.google.com/admanager/answer/7496282).

Mỗi người dùng chỉ giữ một mã chưa dùng còn hạn cho một sản phẩm; gọi lại cùng phiên hoặc phiên khác trả lại mã đó. `GET .../shipping-offer/code` yêu cầu đăng nhập và trả mã hiện tại để tiếp tục sau khi tải lại trang. Thời hạn mặc định 7 ngày, cấu hình bằng `SHIPPING_REWARDS_COUPON_DAYS`; thời hạn phiên bằng `SHIPPING_REWARDS_SESSION_MINUTES`.

Checkout nhận `shipping_discount_code` riêng với các mã giảm tiền hàng. Mã áp dụng một lần, đúng người dùng/sản phẩm, chỉ khi có phí vận chuyển Hà Nội lớn hơn 0 và báo phí hiện tại đã được chấp nhận; giảm 100% phí đang cấu hình, không giảm tiền hàng. Server kiểm tra và sử dụng mã trong cùng giao dịch tạo đơn, giữ nguyên kết quả khi gửi lại cùng `Idempotency-Key`. Tắt chương trình ngừng phát mã mới; mã đã phát còn hạn vẫn có thể dùng theo các điều kiện trên.

## Docker

One Dockerfile builds an image with four runtime roles:

| Service | `DEPLOY_ROLE` | Local address | Processes |
| --- | --- | --- | --- |
| `web` | `web` | `http://localhost:8000` | FrankenPHP/Caddy and Laravel API |
| Free Render API | `web-worker` | Render API URL | FrankenPHP/Caddy, database queues, and scheduler |
| `reverb` | `reverb` | `http://localhost:8081` | Reverb HTTP/WebSocket server |
| `worker` | `worker` | No HTTP port | Database queues and scheduler |

```bash
docker compose build
docker compose run --rm web php artisan migrate --force
docker compose up -d
docker compose logs -f web reverb worker
```

Compose uses a dedicated SQLite/storage volume and local mail/media drivers. Containers publish broadcasts to `http://reverb:10000`; browsers connect to `ws://localhost:8081`. All three containers use the same database, cache store, and cache prefix. Run migrations before starting the services for the first time.

## Render Free: API + worker cùng container, Reverb riêng

### Cấu hình cho 0.5 CPU, 512 MB RAM và DB có độ trễ cao

Docker mặc định giới hạn HTTP ở **1 luồng PHP khi `DEPLOY_ROLE=web-worker`**, hoặc **2 luồng khi `DEPLOY_ROLE=web`**. PHP dùng `memory_limit=128M`, OPcache 64 MB và tắt JIT. Go dùng `GOMAXPROCS=1`, `GOMEMLIMIT=64MiB`; đây là ngưỡng mềm của Go heap, không phải giới hạn RAM của cả container. HTTP nén gzip mức 1 để giảm CPU. Các giới hạn luồng theo hướng dẫn [FrankenPHP](https://frankenphp.dev/docs/performance/).

Một queue worker dùng PHP tối đa 128 MB, tự khởi động lại giữa các job khi vượt ngưỡng 96 MB, sau 500 job hoặc sau 1 giờ. Worker ngủ 3 giây khi queue rỗng, heartbeat ghi mỗi 30 giây. Việc này giảm truy cập DB nền; job vừa đến lúc worker đang ngủ có thể chờ thêm gần 3 giây, ngoài độ trễ DB và thời gian xử lý. Ngưỡng recycle không ngắt job đang chạy; timeout vẫn là 60 giây, nhỏ hơn `retry_after=90`. Xem [queue workers](https://laravel.com/docs/13.x/queues#running-the-queue-worker).

Entrypoint đặt các mặc định bên dưới; có thể ghi đè bằng biến môi trường:

| Biến | `web-worker` | `web` / worker riêng |
| --- | --- | --- |
| `FRANKENPHP_NUM_THREADS` | `1` | `2` cho `web` |
| `FRANKENPHP_MAX_THREADS` | bằng `NUM_THREADS` | bằng `NUM_THREADS` |
| `QUEUE_SLEEP` | `3` | `3` cho worker |
| `QUEUE_MEMORY` | `96` (MB, recycle giữa job) | `96` |
| `QUEUE_MEMORY_LIMIT` | `128M` | `128M` |
| `QUEUE_MAX_JOBS` | `500` | `500` |
| `QUEUE_MAX_TIME` | `3600` (giây) | `3600` |

`REFERENCE_CACHE_STORE=file` dành riêng cho dữ liệu tỉnh/phường, cache tối đa 24 giờ và dùng lại cho kiểm tra/định dạng địa chỉ. Sau khi cache nóng, các bước này không cần SQL tới bảng `addresses`. Khi cập nhật dữ liệu địa giới, chạy `php artisan cache:clear --store=file` trên từng API instance, hoặc đợi hết TTL. Filesystem tạm thời mất cache khi container bị thay thế; request tiếp theo tự tạo lại cache. Có thể chọn store khác qua biến này.

Giữ `CACHE_STORE=database` khi API và worker ở các service khác nhau: khóa upload, scheduler, trạng thái online và heartbeat cần dùng chung. Nếu **chỉ có một API instance**, có thể đặt `CACHE_LIMITER_STORE=file` để rate limiter tránh chờ DB. `render.free.yaml` đã bật lựa chọn này cho API đơn; bỏ biến hoặc chuyển sang store chung trước khi tăng số API instance. Không dùng `array` làm cache production.

Các API được giảm dữ liệu/truy vấn mà vẫn tính số liệu hiện tại: thống kê tương tác còn 4 truy vấn cho cả khách và người đăng nhập; thống kê đánh giá và thống kê đơn hàng mỗi nhóm dùng 1 truy vấn tổng hợp; badge tin nhắn/thông báo dùng chung 1 truy vấn. Catalog/home chỉ tải 1 biến thể phù hợp để hiển thị giá cho mỗi sản phẩm; trang chi tiết vẫn tải đầy đủ biến thể. Giá, tồn kho và điều kiện đặt hàng tiếp tục được kiểm tra khi ghi đơn.

Sau khi cập nhật code, **rebuild và redeploy** image để áp dụng PHP/Caddy/Supervisor; chỉ chạy `config:cache` không áp dụng các cấu hình này. Không cần migration mới cho phần tối ưu. Với Render cấu hình thủ công, đặt `DEPLOY_ROLE=web-worker`, `REFERENCE_CACHE_STORE=file`, và `CACHE_LIMITER_STORE=file` khi API chỉ có một instance. Entrypoint tự đặt các giá trị còn lại và chạy `optimize`.

Giải mã, xoay theo hướng ảnh, chuyển ảnh động thành ảnh tĩnh, thu nhỏ và nén WebP đều chạy trên trình duyệt. Backend chỉ kiểm tra nhẹ header, định dạng và dung lượng ảnh WebP đã chuẩn bị, ghi nguyên byte vào R2 và lưu tham chiếu trong database; không giải mã hoặc mã hóa lại ảnh. `ImageService` chỉ còn trách nhiệm lưu trữ; ImageManager, Intervention và các extension GD/EXIF đã được bỏ khỏi cấu hình backend/Docker.

Đây là cấu hình khởi điểm cho tài nguyên nhỏ, chưa phải kết quả benchmark ở quota thực. Cần theo dõi RAM toàn container, CPU throttling, độ trễ p95 và backlog queue với DB production. Không tăng số luồng hoặc worker trước khi đo RAM còn trống. Giới hạn bộ nhớ PHP không bao gồm toàn bộ bộ nhớ native và tiến trình nền. Đặt API và DB cùng region khi có thể để giảm thời gian mỗi lượt truy vấn.

Nếu đang dùng **Free**, giữ hai Docker **Web Service** hiện tại: `deal-hay-backend` và `deal-hay-reverb`. API chạy thêm queue và scheduler bằng `DEPLOY_ROLE=web-worker`. Render không có Background Worker miễn phí; pre-deploy command chỉ có trên compute trả phí. Xem [Render Free](https://render.com/docs/free) và [pre-deploy command](https://render.com/docs/deploys#pre-deploy-command).

### 1. Sửa lỗi `Pusher ... auth_key ... null given`

Lỗi này cho biết `REVERB_APP_KEY` đang thiếu trong container Reverb. `APP_KEY` là key mã hóa của Laravel, khác với `REVERB_APP_KEY`; điền `APP_KEY` thôi chưa đủ.

Mở **Environment Groups → deal-hay-production**. Nếu chưa có nhóm này, chọn **New Environment Group**, đặt tên `deal-hay-production` và tạo nhóm với các biến hiện có của API, bao gồm `APP_KEY` và `DB_URL`. Điền đủ ba biến dưới đây và dùng **cùng giá trị trên cả hai service**:

| Biến | Giá trị |
| --- | --- |
| `REVERB_APP_ID` | App ID đang dùng; nếu chưa có, đặt `deal-hay` |
| `REVERB_APP_KEY` | App key đang dùng; nếu chưa có, tạo một chuỗi ngẫu nhiên |
| `REVERB_APP_SECRET` | App secret đang dùng; nếu chưa có, tạo một chuỗi ngẫu nhiên khác |

Nếu chưa có key và secret, chạy hai lần trong terminal máy cá nhân có PHP:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Lấy kết quả lần thứ nhất làm `REVERB_APP_KEY`, lần thứ hai làm `REVERB_APP_SECRET`. Chỉ lưu trên Render. Giữ nguyên `APP_KEY` và bộ Reverb credentials nếu chúng đã tồn tại trong deployment cũ.

Vào từng service → **Environment → Linked Environment Groups → chọn deal-hay-production → Link**. Trong **Environment Variables** của từng service, xóa các bản `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` trống hoặc khác với nhóm. Biến riêng của service luôn được ưu tiên hơn biến trong nhóm. Xem [Environment groups](https://render.com/docs/configure-environment-variables#environment-groups).

### 2. Cấu hình nhóm dùng chung cho hai service

Giữ DB MySQL bên ngoài hiện tại và các biến `DB_URL`, `APP_KEY`, `R2_*`, `MAIL_*`, `RESEND_API_KEY`, `ADMIN_*` đang dùng. Thêm hoặc kiểm tra:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://deal-hay-backend.onrender.com
FRONTEND_URL=https://deal-hay.vercel.app
DB_CONNECTION=mysql
CACHE_STORE=database
CACHE_PREFIX=deal-hay-cache
QUEUE_CONNECTION=database
QUEUE_NAMES=images,default
SESSION_DRIVER=database
BROADCAST_CONNECTION=reverb
MAIL_MAILER=resend
MAIL_FROM_NAME="Deal Hay"
LOG_CHANNEL=stderr
LOG_LEVEL=info
CORS_ALLOWED_ORIGINS=https://deal-hay.vercel.app,https://deal-hay-fontend.vercel.app
REVERB_ALLOWED_ORIGINS=deal-hay.vercel.app,deal-hay-fontend.vercel.app
```

Điền thêm `MAIL_FROM_ADDRESS` và `RESEND_API_KEY` theo [cấu hình Resend](#gửi-email-trên-render-bằng-resend) ở trên. Nếu database mới, điền `ADMIN_FULL_NAME`, `ADMIN_EMAIL`, `ADMIN_PHONE_NUMBER`, `ADMIN_PASSWORD` trước khi API khởi động chạy migrations. Nếu frontend dùng tên miền khác, cập nhật `FRONTEND_URL` và hai danh sách origins theo tên miền thật.

### 3. Dùng URL công khai của Reverb

Mở service `deal-hay-reverb`, sao chép hostname trong URL công khai Render cấp. Ví dụ URL `https://deal-hay-reverb.onrender.com` có hostname `deal-hay-reverb.onrender.com`. Thêm vào nhóm dùng chung:

```env
REVERB_HOST=deal-hay-reverb.onrender.com
REVERB_PORT=443
REVERB_SCHEME=https
REVERB_PUBLIC_HOST=deal-hay-reverb.onrender.com
REVERB_PUBLIC_PORT=443
REVERB_PUBLIC_SCHEME=https
```

Thay hostname ví dụ bằng hostname thật. `REVERB_HOST` và `REVERB_PUBLIC_HOST` không chứa `https://`, dấu `/` hay cổng. API/queue publish qua HTTPS; trình duyệt kết nối WebSocket qua WSS. Free Web Service không nhận kết nối private network, nên dùng hostname công khai cho cả hai. Xem [Render private network](https://render.com/docs/private-network).

Xóa các biến riêng cũ `REVERB_HOST`, `REVERB_PORT`, `REVERB_SCHEME`, `REVERB_PUBLIC_HOST`, `REVERB_PUBLIC_PORT`, `REVERB_PUBLIC_SCHEME` nếu chúng đang ghi đè cấu hình nhóm bằng hostname nội bộ hoặc cổng `10000`. Bỏ override `PORT`, `REVERB_SERVER_HOST`, `REVERB_SERVER_PORT`; Render cấp `PORT` và entrypoint cho Reverb nghe tại `0.0.0.0:$PORT`.

### 4. Đẩy code mới rồi cấu hình hai service

Commit và push các thay đổi deployment lên branch mà hai service đang dùng. Cả hai chọn compute **Free**, runtime **Docker**, cùng repository/branch.

| Mục | `deal-hay-backend` | `deal-hay-reverb` |
| --- | --- | --- |
| Biến riêng `DEPLOY_ROLE` | `web-worker` | `reverb` |
| Biến riêng `RUN_MIGRATIONS` | `true` | `false` |
| Dockerfile Path | `./Dockerfile` | `./Dockerfile` |
| Docker Build Context | `.` | `.` |
| Docker Command | Để trống | Để trống |
| Health Check Path | `/up` | `/up` |
| Pre-deploy Command (nếu đang có) | Xóa lệnh cũ | Để trống |

Nếu GitHub chứa cả frontend và backend, đặt **Root Directory = deal-hay-backend** cho hai service; nếu repo chỉ chứa backend thì để trống. Giữ `RUN_MIGRATIONS` ở biến riêng, không đặt chung trong environment group.

API chạy `php artisan migrate --force --no-interaction` khi container khởi động rồi chạy HTTP, queue và scheduler. Lệnh migrate chỉ áp dụng những migrations chưa chạy. Reverb không chạy migrations. Cách này không cần Render Shell hay pre-deploy command trên gói Free.

Chọn **Save, rebuild, and deploy** nếu vừa sửa code/Dockerfile, hoặc **Manual Deploy → Deploy latest commit**. Deploy API trước để migrations tạo đủ bảng database/cache, rồi deploy Reverb. Không cần tạo service `deal-hay-worker` riêng cho cấu hình Free.

### 5. Kiểm tra deploy

Mở URL công khai thực tế của hai service:

| Endpoint | Kết quả |
| --- | --- |
| API `/up` | HTTP `200` khi API, database và cache hoạt động |
| Reverb `/up` | `{"health":"OK"}` |
| API `/ready` | HTTP `200`, `status: ok` và database/cache/outbox_worker/reverb đều `ok` khi tất cả sẵn sàng |

Trong **Logs của API**, Supervisor phải khởi động cả HTTP server, queue và scheduler. Nếu `/ready` trả `503`, đọc tên check `failed`: `outbox_worker` cần kiểm tra queue trong API; `reverb` cần kiểm tra URL công khai, credentials và trạng thái Reverb. Sau khi Reverb vừa thức dậy từ chế độ ngủ, chờ service khởi động xong rồi tải lại `/ready`.

Frontend Vercel giữ `NEXT_PUBLIC_API=https://deal-hay-backend.onrender.com/api`; API trả cấu hình WebSocket công khai cho trình duyệt.

### Blueprint Free (tùy chọn)

`render.free.yaml` mô tả đúng hai Web Service với `plan: free`, queue/scheduler chạy cùng API và publisher Reverb dùng HTTPS công khai. `render.yaml` bên dưới dành cho ba service **trả phí**; chọn **Blueprint Path = render.free.yaml** nếu deploy theo Blueprint Free.

Tạo và điền nhóm `deal-hay-production` trước khi sync; file Free chỉ liên kết nhóm hiện có, không tạo lại hoặc sinh lại credentials. Khi áp dụng vào service cũ, bỏ pre-deploy command trong Dashboard trước. File dùng đúng tên `deal-hay-backend`/`deal-hay-reverb` để Render có thể áp dụng vào hai service hiện tại; sửa tên và mọi `fromService.name` nếu service thật có tên khác. Xem [Blueprint reference](https://render.com/docs/blueprint-spec).

Blueprint giả định repo root là backend. Với monorepo, đặt `dockerfilePath: ./deal-hay-backend/Dockerfile` và `dockerContext: ./deal-hay-backend` trong cả hai service; chọn Blueprint Path là `deal-hay-backend/render.free.yaml`. `region: singapore` phải trùng region của service hiện có vì Render không cho đổi region sau khi tạo.

### Giới hạn Free

Render cho Web Service ngủ sau 15 phút không có lưu lượng HTTP hoặc tin nhắn WebSocket; khi API ngủ, queue và scheduler trong container cũng dừng. Job lưu trong database sẽ được xử lý tiếp khi API hoạt động lại, nhưng tác vụ theo lịch không chạy liên tục. Hai service dùng chung hạn mức **750 giờ chạy mỗi workspace/tháng**; chúng không thể cùng chạy 24/7 suốt tháng trong hạn mức này. Xem [Render Free](https://render.com/docs/free).

## Render paid production deployment

Render exposes one public HTTP port per Web Service, so the API and realtime server have separate services and URLs. Each can use Render's default `PORT=10000` because they run in separate containers. A Background Worker processes the queue without an HTTP port. See [Render port binding](https://render.com/docs/web-services#port-binding) and [Background Workers](https://render.com/docs/background-workers).

### Update the existing API service manually

Keep the existing `deal-hay-backend` Web Service to preserve `https://deal-hay-backend.onrender.com`. Create a second Docker Web Service for Reverb and a Docker Background Worker from the same backend repository, branch, and Dockerfile. Use the same Render region for all three.

| Render service | Type | Environment | Health check | Pre-deploy command |
| --- | --- | --- | --- | --- |
| Existing `deal-hay-backend` | Web Service | `DEPLOY_ROLE=web` | `/up` | `php artisan migrate --force` |
| New `deal-hay-reverb` | Web Service | `DEPLOY_ROLE=reverb` | `/up` | None |
| New `deal-hay-worker` | Background Worker | `DEPLOY_ROLE=worker` | None | None |

Leave the Docker command empty so the image's entrypoint selects the role. Link a common environment group to all three services with the existing `APP_KEY`, database credentials, Reverb credentials, and application/provider settings. In particular, these values must match between the API and worker:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://deal-hay-backend.onrender.com
DB_CONNECTION=mysql
DB_URL=mysql://USER:PASSWORD@HOST:PORT/DATABASE
CACHE_STORE=database
CACHE_PREFIX=deal-hay-cache
QUEUE_CONNECTION=database
QUEUE_NAMES=images,default
SESSION_DRIVER=database
BROADCAST_CONNECTION=reverb
```

Set the same `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` on all three services. Reuse existing credentials when updating an existing deployment. Set `FRONTEND_URL`, `CORS_ALLOWED_ORIGINS`, and `REVERB_ALLOWED_ORIGINS` for the Vercel frontend domains that should connect.

After creating the realtime service, open its **Connect → Internal** details and configure the publisher endpoint on all three services. Use its actual internal hostname, without a protocol or port in `REVERB_HOST`:

```env
REVERB_HOST=YOUR_REVERB_INTERNAL_HOSTNAME
REVERB_PORT=10000
REVERB_SCHEME=http
REVERB_PUBLIC_HOST=deal-hay-reverb.onrender.com
REVERB_PUBLIC_PORT=443
REVERB_PUBLIC_SCHEME=https
```

Replace `deal-hay-reverb.onrender.com` with the actual public hostname Render assigns. `REVERB_HOST` is used by the API/worker to publish over the private network; `REVERB_PUBLIC_HOST` is returned to browsers for their WebSocket connection. Browsers need the public hostname. On a free Reverb Web Service, private inbound networking is unavailable; a temporary public publisher endpoint uses that public hostname with `REVERB_PORT=443` and `REVERB_SCHEME=https`. See [Render private networking](https://render.com/docs/private-network).

Reverb binds to `0.0.0.0:$PORT` automatically in its container. Keep `REVERB_SERVER_HOST` and `REVERB_SERVER_PORT` unset in the Dashboard; the entrypoint sets them before caching Laravel configuration. Render terminates TLS, so the server inside the container uses HTTP.

Redeploy all three services after updating environment variables. The API's `/up` checks the API, database, and cache independently of worker startup. The realtime service's `/up` returns `{"health":"OK"}` from Reverb itself. If that JSON appears at the API URL, check that the API service has `DEPLOY_ROLE=web` and that the URLs point to the intended services.

Check `https://deal-hay-backend.onrender.com/ready` after deploying. It returns HTTP `200` with `status: ok` when database, cache, outbox worker, and Reverb checks pass, or HTTP `503` with `status: degraded` and the failed checks. Keep Render's API health check at `/up` so a worker/realtime outage does not prevent the API from deploying.

If `outbox_worker` fails, inspect the Background Worker's logs, verify its `DEPLOY_ROLE=worker`, and confirm its `DB_URL`, `CACHE_STORE`, and `CACHE_PREFIX` match the API. Supervisor runs a dedicated `realtime` worker (`php artisan queue:work database_outbox --queue=realtime --sleep=0.1`) alongside the `images,default` worker, so image cleanup does not delay notifications or chat. The `images` queue name is retained for storage cleanup; it no longer transforms images. Set `DB_OUTBOX_QUEUE` consistently if using a different realtime queue name. The realtime worker records the outbox heartbeat; a worker started on the ordinary `database` queue connection does not. A stopped worker is reported within two minutes. If `reverb` fails, check the publisher hostname/port and matching Reverb credentials/origins.

### Deploy with the Blueprint

`render.yaml` provisions three paid services in Singapore:

- `deal-hay-api`: FrankenPHP/Caddy and Laravel HTTP; health check at `/up`.
- `deal-hay-reverb`: standalone Reverb; health check at `/up`.
- `deal-hay-worker`: all database queues plus `schedule:work`.

The Blueprint uses `deal-hay-api` as the API name. To adopt the existing `deal-hay-backend` service, update the API's `name` and every `fromService.name: deal-hay-api` reference to the existing service name before syncing. Preserve the existing `APP_KEY` and Reverb credentials in the shared environment group.

The Blueprint assumes `deal-hay-backend` is the Git repository root. If it is later placed in a monorepo, update `dockerfilePath` and `dockerContext` relative to that repository root before syncing the Blueprint.

The API service runs `php artisan migrate --force` as Render's pre-deploy command. The runtime entrypoint then runs `php artisan optimize`, after production environment variables are available. No persistent disk is required because the database, cache/session data, queues, and R2 media are external.

The Blueprint derives `APP_URL` from the API's `RENDER_EXTERNAL_URL`, publisher host/port from the realtime service's private `host`/`port`, and browser host from its `RENDER_EXTERNAL_HOSTNAME`. `fromService.property: host` provides an internal hostname; it does not provide the public `onrender.com` hostname. See [Render service property references](https://render.com/docs/blueprint-spec#referencing-service-properties).

The image removes FrankenPHP's inherited `CAP_NET_BIND_SERVICE` before switching to the `app` user, following [FrankenPHP's guidance for running without capabilities](https://frankenphp.dev/docs/docker/#running-with-no-capabilities). Port `10000` does not need that capability. Restricted runtimes can otherwise reject execution with `couldn't exec frankenphp: EPERM`, leaving no public HTTP listener. The build also runs `frankenphp version` as `app` to verify the executable.

Create a Render Blueprint from this repository and fill every blank value in the `deal-hay-production` environment group before the first successful deploy. In particular:

- Generate `APP_KEY` once with `php artisan key:generate --show`; never regenerate it during startup.
- Set `FRONTEND_URL` to the production Vercel URL. The Blueprint sets `APP_URL` per service automatically.
- Set `DB_URL` to the external MySQL connection URL.
- Set the Resend, administrator, and R2 values declared in `render.yaml`.
- The Blueprint sets Reverb's public hostname automatically, with port `443` and scheme `https`.
- Do not add `PORT`; Render provides it automatically.

Remove stale per-service Dashboard overrides for `CACHE_STORE`, `CACHE_PREFIX`, and `REVERB_*` that conflict with the shared group or Blueprint. Render preserves variables that a later Blueprint revision omits; this includes old `REVERB_HOST`, `REVERB_PORT`, and `REVERB_PUBLIC_HOST` values in an existing environment group.

The configured pre-deploy command and background worker require paid Render compute. For Free compute, use the two-service configuration and `render.free.yaml` described above.

## Vercel frontend

### Upload nhiều ảnh và tiến độ

Frontend thực hiện toàn bộ xử lý ảnh trên trình duyệt: giải mã, xoay theo hướng ảnh, lấy một khung hình tĩnh, thu nhỏ và nén WebP. Ảnh sản phẩm có ngân sách **512 KiB (524.288 byte)** mỗi ảnh để giữ chi tiết sản phẩm; avatar, ảnh đánh giá và ảnh chat tối đa **20 KiB (20.480 byte)** mỗi ảnh. Cạnh dài tối đa 1280 px, riêng avatar 640 px; có thể giảm thêm kích thước để đạt ngân sách dung lượng tương ứng. Chỉ giải mã một ảnh cùng lúc và gửi tối đa hai ảnh đồng thời. Thumbnail xem trước cũng được thu nhỏ để tránh giữ nhiều ảnh camera đã giải mã trong RAM trình duyệt. Nếu trình duyệt không giải mã hoặc xuất WebP được, biểu mẫu báo lỗi; ảnh gốc không được gửi sang backend để xử lý thay.

Một sản phẩm nhận tối đa 50 ảnh bổ sung và một ảnh chính, mỗi ảnh gốc chọn trên trình duyệt tối đa 30 MiB. Trình duyệt chuẩn bị WebP trước, khai báo một lô rồi gửi từng ảnh qua `/api/live/uploads/images/prepare` tới Laravel `/private/uploads/images/prepare`; request lưu sản phẩm chỉ gửi token ảnh đã lưu. Avatar, ảnh đánh giá và ảnh chat cũng được chuẩn bị tại trình duyệt trước khi gửi. Backend kiểm tra header/dung lượng của WebP tĩnh cuối cùng, ghi nguyên nội dung vào R2 và lưu tham chiếu ảnh trong database. Không còn grant hoặc endpoint chuyển đổi ảnh ở backend. Quyền sở hữu token ảnh tạm, quota, khóa chống sử dụng trùng, retry và dọn ảnh tạm/ảnh thay thế vẫn được giữ. Danh sách ảnh gallery được thêm/xóa bằng truy vấn theo lô để giảm thời gian chờ DB.

PHP dùng `upload_max_filesize=3M`, `post_max_size=32M` và `max_file_uploads=51` cho cả Docker và `php artisan serve`. Mức này vẫn nhận tệp chat không phải ảnh tối đa 3 MiB hoặc lô multipart gồm 51 WebP sản phẩm tối đa 512 KiB mỗi ảnh. Luồng tạo sản phẩm trên frontend vẫn gửi từng ảnh qua staging, tối đa hai yêu cầu đồng thời. Giới hạn 30 MiB chỉ áp dụng ảnh gốc tại trình duyệt. Bộ nhớ PHP mặc định cho server phát triển và production đều là 128 MB.

Giao diện hiển thị phần trăm theo dữ liệu thực từ XHR, từng ảnh và cả đợt, kèm giai đoạn chuẩn bị/gửi/lưu. Chỉ xác nhận hoàn tất sau khi backend phản hồi đã lưu. Khi lỗi, biểu mẫu giữ lựa chọn để người dùng thử lại; các request ảnh còn lại của đợt được hủy. `CORS_MAX_AGE=600` cho phép trình duyệt dùng lại preflight 10 phút; danh sách origins và kiểm tra token vẫn giữ nguyên.

Triển khai frontend và backend cùng phiên bản, rebuild Docker để bỏ thư viện xử lý ảnh và khởi động lại worker. Không cần migration database cho thay đổi này; tham chiếu và URL ảnh cũ vẫn dùng được. Backend trả `422` nếu client cũ gửi ảnh gốc hoặc ảnh chưa đạt hợp đồng WebP cuối cùng.

### Trang Độ trễ trên header

Header và menu mobile có liên kết **Độ trễ** tới `/diagnostics`, dành cho tài khoản đang đăng nhập. Phép đo mạng hiển thị ping HTTP, jitter và tốc độ tải xuống/tải lên giữa trình duyệt với máy chủ API. Dữ liệu đo truyền trực tiếp đến API; thời gian tạo phiên ở Next.js được tách khỏi mẫu ping và tốc độ. Ping là thời gian gửi yêu cầu HTTP và nhận phản hồi, bao gồm xử lý ứng dụng, không phải ICMP. Chạy theo yêu cầu của người dùng, không có polling nền.

`POST /api/diagnostics/speedtest/session` xác thực tài khoản đang hoạt động và cấp token chỉ dùng để đo mạng, hết hạn sau 120 giây; token đăng nhập không được đưa xuống trình duyệt. Các endpoint `/ping`, `/download` và `/upload` dưới `/api/diagnostics/speedtest` chỉ nhận token đo này. Mỗi mẫu tải xuống tối đa 4 MiB dữ liệu ngẫu nhiên khó nén; tải lên tối đa 2 MiB, đọc rồi bỏ dữ liệu. Mỗi phiên tối đa 64 yêu cầu và ngân sách 64 MiB, trong đó mỗi lượt upload giữ trước 2 MiB để không tin dung lượng do client khai báo. Không lưu payload vào database/R2 và không gọi phép kiểm tra dependency trong lượt đo mạng. Phiên đo mới giới hạn 3 lần/phút/tài khoản; các probe còn có limiter theo token/IP.

Metadata token và ngân sách phiên dùng store của `CACHE_LIMITER_STORE` khi được cấu hình, nếu không dùng `CACHE_STORE`. Với một API instance, `CACHE_LIMITER_STORE=file` như cấu hình Free giúp tránh các lượt ghi metadata tới DB từ xa; nhiều instance cần store dùng chung. Cấu hình `CORS_ALLOWED_ORIGINS`/`FRONTEND_URL` phải cho phép origin frontend, `Authorization`, `Content-Type` và `X-Speedtest-Bytes`. Các mẫu dùng URL cố định; tải xuống chọn dung lượng qua header `X-Speedtest-Bytes` để giữ lại preflight CORS đã làm nóng khi đổi cỡ mẫu. Query `bytes` vẫn hỗ trợ khi không có header này. Các mẫu đo đặt `no-store`/`no-transform`; tải xuống dùng `application/octet-stream`, độ dài cố định và `Content-Encoding: identity`.

`GET /api/diagnostics` yêu cầu Sanctum và tài khoản hoạt động, giới hạn 6 lần/phút/tài khoản. API chỉ truy vấn `SELECT 1`, đọc cache, gọi `HeadBucket` R2 và bắt tay WebSocket Reverb; không ghi/tải ảnh. R2 giới hạn một lần thử với connect timeout 2 giây, request timeout 3 giây. Endpoint chỉ đo các dịch vụ đã cấu hình, không nhận URL tùy ý, không trả hostname, khóa hay lỗi kết nối nội bộ. R2/realtime dùng local hoặc bị tắt sẽ được bỏ qua; lỗi dependency xuất hiện trong kết quả HTTP 200, độc lập endpoint readiness `/ready`.

Chat và thông báo dùng một kết nối Echo/Reverb chung, giữ kết nối khi chuyển trang. Tin nhắn mới, trạng thái đã đọc và badge cập nhật theo sự kiện WebSocket; không có fallback tải lại trang mỗi 15 giây. Sau khi subscribe hoặc kết nối lại, frontend lấy một snapshot để bù dữ liệu bị lỡ. Heartbeat định kỳ chỉ cập nhật trạng thái online, không tải tin nhắn.

Để xác minh trên trình duyệt, mở DevTools → Network → WS: kết nối `/app/{REVERB_APP_KEY}` phải nhận HTTP `101`, sau đó có `pusher:subscription_succeeded` cho kênh cá nhân. Khi gửi từ tài khoản khác, phải có frame `message.sent` ngay khi queue worker publish, không cần đợi refresh trang. Nếu UI báo mất kết nối, kiểm tra `/ready`, log worker, public hostname/origins và `/api/broadcasting/auth`; nút **Kết nối lại** sẽ lấy lại cấu hình và tạo socket mới. Cấu hình backend cần `BROADCAST_CONNECTION=reverb`; endpoint realtime trả `503` nếu broadcaster đang tắt.

Configure the current frontend's API variable for each Vercel environment and redeploy after changing it:

```env
NEXT_PUBLIC_API=https://your-api-host.onrender.com/api
```

The frontend reads this URL from a `server-only` module. Next.js replaces `NEXT_PUBLIC_API` at build time, so changing it requires a new deployment. No backend credential belongs in this value.

## Coupon sản phẩm, kiểm kê và tải ảnh

Khi triển khai bản cập nhật này, chạy `php artisan migrate --force`, triển khai lại frontend và khởi động lại các tiến trình Supervisor để nạp worker realtime riêng. Với Render, dùng quy trình deploy đã mô tả phía trên; cập nhật biến `QUEUE_NAMES=images,default` trong Dashboard nếu đang đặt giá trị cũ. Giữ `DB_OUTBOX_QUEUE=realtime` thống nhất giữa API và worker, cùng cấu hình Reverb đang dùng.

Shop tạo coupon bằng tên hiển thị và chọn sản phẩm áp dụng; mã do hệ thống sinh và không thể sửa. Người mua xem **Ưu đãi từ shop** tại trang shop và chọn coupon trong checkout. Coupon có sẵn từ trước bản cập nhật cần được shop mở sửa và gán sản phẩm trước khi hiển thị cho người mua.

Trong **Quản lý shop → Kho hàng**, mỗi đơn vị tồn có SKU riêng dạng `DHU-…` và nội dung QR chỉ chứa chính mã SKU đó; tồn 2 cấp đúng 2 mã khác nhau. Danh sách tự bổ sung mã cho tồn kho cũ khi mở kho hoặc bắt đầu kiểm kê. Chọn từng đơn vị để in tem (60 × 32 mm, bố trí A4); in lại giữ nguyên SKU, không tạo hàng mới. Tem `DH:U:{uuid}` đã in trước đây vẫn quét được. Danh sách tem và chi tiết phiên có phân trang; select, checkbox và bộ chọn ưu đãi dùng component shadcn của dự án.

Mở phiên rồi quét bằng máy quét có Enter/Tab hoặc camera trên HTTPS. Ô SKU tự nhận con trỏ khi mở phiên và sau khi lưu, tải lại hoặc hoàn tác. Khi bắn nhanh nhiều mã, ô nhập vẫn nhận dữ liệu và hàng đợi gửi từng SKU lần lượt; nếu lỗi kết nối, giữ nguyên các mã chưa xác nhận để thử lại cùng mã yêu cầu hoặc bỏ qua rõ ràng. Camera chờ hình ảnh sẵn sàng, giải phóng đúng luồng của từng lần mở và báo lỗi khi bộ đọc dừng bất thường. Hệ thống nhận SKU không phân biệt hoa/thường và bỏ ký tự kết thúc CR/LF/Tab của máy quét; mỗi lượt gửi một SKU riêng. Mỗi tem chỉ được tính một lần trong phiên, kể cả quét bằng cả QR mới và tem cũ hoặc retry request. Mã SKU chung của mặt hàng và tem `DH:P`/`DH:V` cũ không dùng để đếm từng đơn vị trong phiên mới. Có thể hoàn tác tem quét nhầm rồi quét lại; **Lưu phiên** giữ dữ liệu để kiểm tiếp. Hàng đang giữ cho đơn hàng không được đưa lại vào tồn khả dụng qua kiểm kê; hủy đơn khôi phục đúng mã đã giữ, không sinh tem thay thế.

**Kiểm tra và chốt phiên** yêu cầu xác nhận số lượng và công nợ. Công nợ thiếu hàng cần đối soát bằng tổng **giá bán hiện tại** của các đơn vị thiếu, dùng giá riêng của biến thể nếu có; giá 0 được giữ nguyên. Giá trị thừa và chênh lệch ròng (thừa − thiếu) theo dõi riêng, không tự bù trừ công nợ thiếu hàng. Mã lạ không được định giá. Một mã thiếu và một mã thừa vẫn được báo riêng dù tổng số lượng bằng nhau. Khi chốt, đơn giá, tổng tiền, người xác nhận và thời điểm được lưu cố định; đổi giá hoặc xóa sản phẩm sau đó không làm đổi biên bản. Nếu số lượng hoặc giá bán đổi trong lúc xác nhận, cần tải lại và kiểm tra trước khi chốt.

Có thể chốt biên bản giữ tồn hoặc chốt và cập nhật tồn theo đúng các mã thiếu/thừa. Cập nhật tồn chỉ thực hiện khi số lượng, danh sách mã và lịch sử biến động còn khớp mốc đầu phiên, tránh ghi đè đơn hàng phát sinh. Xuất CSV tải đủ các trang mã và kiểm tra phiên không thay đổi giữa lúc xuất. Các phiên cũ được giữ ở chế độ số lượng gộp, không tự suy ra công nợ lịch sử; mở phiên mới để kiểm theo từng mã.

Tạo sản phẩm chuẩn bị tối đa 50 ảnh thư viện cùng một ảnh chính thành WebP tĩnh tối đa 512 KiB mỗi ảnh ở trình duyệt, khai báo một lô rồi tải tối đa hai ảnh đồng thời. Request lưu sản phẩm chỉ gửi mã ảnh đã lưu. Token ảnh thuộc từng tài khoản và có thể retry khi lượt tải đang bận; không gửi toàn bộ thư viện ảnh trong một request lưu sản phẩm.

## Useful commands

```bash
php artisan route:list
php artisan test
vendor/bin/pint
```

See `docs/api-contract.md` for the endpoint contract used by the frontend.
