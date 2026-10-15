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

Mã đăng nhập admin, mã đổi email và liên kết đặt lại mật khẩu được gửi trực tiếp. Email thông báo đổi mật khẩu thành công và khóa/mở khóa tài khoản vẫn cần worker xử lý queue `default`; trên Render Free, dùng `DEPLOY_ROLE=web-worker` và `QUEUE_NAMES=realtime,images,default` cho API như hướng dẫn bên dưới. Nếu mail vẫn lỗi sau khi deploy, kiểm tra log API/worker và Resend Dashboard để phân biệt API key không hợp lệ, domain chưa xác minh hay lỗi giao thư.

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
QUEUE_NAMES=realtime,images,default
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
QUEUE_NAMES=realtime,images,default
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

If `outbox_worker` fails, inspect the Background Worker's logs, verify its `DEPLOY_ROLE=worker`, and confirm its `DB_URL`, `CACHE_STORE`, and `CACHE_PREFIX` match the API. The supervised queue command is `php artisan queue:work database_outbox --queue=realtime,images,default`; the realtime queue must be included for heartbeat recording. A worker started on the ordinary `database` queue connection does not record this outbox heartbeat. A stopped worker is reported within two minutes. If `reverb` fails, check the publisher hostname/port and matching Reverb credentials/origins.

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

Configure the current frontend's API variable for each Vercel environment and redeploy after changing it:

```env
NEXT_PUBLIC_API=https://your-api-host.onrender.com/api
```

The frontend reads this URL from a `server-only` module. Next.js replaces `NEXT_PUBLIC_API` at build time, so changing it requires a new deployment. No backend credential belongs in this value.

## Useful commands

```bash
php artisan route:list
php artisan test
vendor/bin/pint
```

See `docs/api-contract.md` for the endpoint contract used by the frontend.
