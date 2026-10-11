<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrowserUploadRequest;
use App\Http\Requests\MediaAssetRequest;
use App\Http\Requests\MediaRequest;
use App\Models\User;
use App\Services\MediaClient;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MediaGatewayController extends Controller
{
    public function __construct(
        private readonly MediaClient $stagedUploads,
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

    public function transform(MediaRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json(
            $this->stagedUploads->browserGrant(
                (string) $user->getKey(),
                (int) $request->validated('size'),
                (string) $request->validated('purpose'),
            ),
        );
    }

    public function browserTransform(BrowserUploadRequest $request): Response
    {
        return response($this->stagedUploads->browserTransform($request->file('image')), headers: [
            'Content-Type' => 'image/webp',
            'Cache-Control' => 'no-store',
        ]);
    }

    public function asset(MediaAssetRequest $request): JsonResponse|Response
    {
        $path = $request->validated('path');
        $url = $this->stagedUploads->assetUrl($path);

        if ($url === null) {
            abort(404);
        }

        return response()->redirectTo($url);
    }
}
