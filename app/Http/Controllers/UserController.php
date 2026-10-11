<?php

namespace App\Http\Controllers;

use App\Actions\AuthenticateUser;
use App\Actions\CompleteAdministratorSignIn;
use App\Actions\GetUserUnreadCounts;
use App\Actions\IssueUserAccessToken;
use App\Actions\LogoutUser;
use App\Actions\RegisterUser;
use App\Actions\ResetUserPassword;
use App\Actions\SendPasswordResetLink;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\SignInRequest;
use App\Http\Requests\SignUpRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function signIn(
        SignInRequest $request,
        AuthenticateUser $authenticateUser,
        CompleteAdministratorSignIn $completeAdministratorSignIn,
        IssueUserAccessToken $issueAccessToken,
    ): JsonResponse {
        $user = $authenticateUser->handle($request->validated());

        if ((int) $user->is_active === UserStatusEnum::INACTIVE) {
            return $this->lockedAccountResponse($user);
        }

        if ($user->role === UserRoleEnum::ADMINISTRATOR) {
            $completeAdministratorSignIn->handle(
                $user,
                $request->validated('admin_verification_code'),
            );
        }

        return response()->json([
            'message' => 'Đăng nhập thành công.',
            'token' => $issueAccessToken->handle($user, $request->userAgent() ?? 'api-client'),
            'user' => $user,
        ]);
    }

    public function logout(Request $request, LogoutUser $logoutUser): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        $logoutUser->handle($user, $request->hasSession() ? $request->session()->getId() : null, $request->bearerToken());

        return response()->json([
            'message' => 'Đăng xuất thành công.',
        ]);
    }

    public function signUp(
        SignUpRequest $request,
        RegisterUser $registerUser,
        IssueUserAccessToken $issueAccessToken,
    ): JsonResponse {
        $user = $registerUser->handle($request->validated());

        return response()->json([
            'message' => 'Đăng ký thành công.',
            'token' => $issueAccessToken->handle($user, $request->userAgent() ?? 'api-client'),
            'user' => $user,
        ], 201);
    }

    public function forgotPassword(
        ForgotPasswordRequest $request,
        SendPasswordResetLink $sendPasswordResetLink,
    ): JsonResponse {
        $sendPasswordResetLink->handle($request->safe()->only(['email']));

        return response()->json([
            'message' => 'Nếu thông tin tài khoản hợp lệ, liên kết đặt lại mật khẩu sẽ được gửi qua email.',
        ]);
    }

    public function resetPassword(
        ResetPasswordRequest $request,
        ResetUserPassword $resetUserPassword,
    ): JsonResponse {
        return response()->json([
            'message' => __($resetUserPassword->handle($request->validated())),
        ]);
    }

    public function me(Request $request, GetUserUnreadCounts $getUserUnreadCounts): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => $user,
            'meta' => $getUserUnreadCounts->handle($user),
        ]);
    }

    private function lockedAccountResponse(User $user): JsonResponse
    {
        return response()->json([
            'message' => 'Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.',
            'lock_reason' => $user->lock_reason,
            'user' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ], 423);
    }
}
