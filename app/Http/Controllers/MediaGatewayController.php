<?php

namespace App\Http\Controllers;

use App\Http\Requests\MediaAssetRequest;
use App\Http\Requests\MediaRequest;
use App\Models\User;
use App\Services\ImageService;
use App\Services\MediaClient;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class MediaGatewayController extends Controller
{
    public function __construct(
        private readonly MediaClient $stagedUploads,
        private readonly ImageService $images,
    ) {}

    public function storeStaged(MediaRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'uploads' => $this->stagedUploads->issueBatch($user, $request->validated('uploads')),
        ]);
    }

    public function discardStaged(MediaRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->stagedUploads->discard($user, $request->validated('tokens'));

        return response()->json([], 204);
    }

    public function storeUploadSessions(MediaRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'uploads' => $this->stagedUploads->issueBatch($user, $request->validated('uploads'), presign: false),
        ]);
    }

    public function prepareStaged(MediaRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->stagedUploads->prepareImage($user, $request->validated('token'), $request->file('image'));

        return response()->json([], 204);
    }

    public function updateProfileAvatar(MediaRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($request->hasFile('avatar')) {
            $this->stagedUploads->replaceAvatar($user, 'avatar_url', $request->file('avatar'));
        }

        return response()->json([
            'message' => 'Cập nhật ảnh đại diện thành công.',
            'data' => $user->refresh(),
        ]);
    }

    public function updateShopAvatar(MediaRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->stagedUploads->replaceAvatar($user, 'shop_avatar_url', $request->file('avatar'));

        return response()->json([
            'message' => 'Cập nhật ảnh cửa hàng thành công.',
            'data' => $user->refresh(),
        ]);
    }

    public function asset(MediaAssetRequest $request): JsonResponse|Response
    {
        $path = $request->validated('path');
        if ($request->header('X-DealHay-Media-Transport') === 'stream') {
            return $this->streamAsset($path);
        }
        $url = $this->stagedUploads->assetUrl($path);

        if ($url === null) {
            abort(404);
        }

        return response()->redirectTo($url);
    }

    private function streamAsset(string $path): Response
    {
        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'webp' => 'image/webp',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'avif' => 'image/avif',
            'bmp' => 'image/bmp',
            default => null,
        };
        abort_if($mime === null, 404);
        try {
            $size = $this->images->size($path);
        } catch (Throwable) {
            abort(404);
        }
        // Existing server-normalized assets were capped at 3 MiB.
        abort_if($size < 1 || $size > 3 * 1024 * 1024, 404);
        try {
            $stream = $this->images->readStream($path);
        } catch (Throwable) {
            abort(404);
        }

        return response()->stream(function () use ($stream, $size): void {
            try {
                $remaining = $size;
                while ($remaining > 0 && ! feof($stream)) {
                    $chunk = fread($stream, min($remaining, 64 * 1024));
                    if ($chunk === false || $chunk === '') {
                        break;
                    }
                    echo $chunk;
                    $remaining -= strlen($chunk);
                }
            } finally {
                fclose($stream);
            }
        }, headers: [
            'Content-Type' => $mime,
            'Content-Length' => (string) $size,
            'Cache-Control' => 'public, max-age=86400, s-maxage=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
