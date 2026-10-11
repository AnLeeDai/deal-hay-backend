<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $isActive ? 'Tài khoản của bạn đã được mở khóa' : 'Tài khoản của bạn đã bị khóa' }}</title>
</head>
<body style="margin: 0; padding: 24px; font-family: Arial, sans-serif; background: #f5f5f5; color: #111827;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h1 style="margin: 0 0 16px; font-size: 24px; line-height: 1.3;">Xin chào {{ $fullName }},</h1>

        @if ($isActive)
            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.7;">
                Tài khoản của bạn đã được quản trị viên mở khóa.
            </p>

            <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.7;">
                Bạn có thể đăng nhập lại và tiếp tục sử dụng hệ thống tại đây:
                <a href="{{ $signInUrl }}" style="color: #2563eb;">{{ $signInUrl }}</a>
            </p>
        @else
            <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.7;">
                Tài khoản của bạn đã bị quản trị viên khóa.
            </p>

            <div style="margin-bottom: 24px; padding: 20px; border-radius: 12px; background: #f9fafb; border: 1px solid #e5e7eb;">
                <p style="margin: 0 0 8px;"><strong>Email:</strong> {{ $email }}</p>
                <p style="margin: 0;"><strong>Lý do:</strong> {{ $lockReason ?: 'Không có lý do cụ thể.' }}</p>
            </div>

            <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.7;">
                Nếu bạn cho rằng đây là nhầm lẫn, vui lòng đăng nhập để gửi yêu cầu hỗ trợ mở khóa.
            </p>
        @endif

        <p style="margin: 0; font-size: 13px; line-height: 1.7; color: #6b7280;">
            Đây là email tự động từ hệ thống. Vui lòng không trả lời trực tiếp email này.
        </p>
    </div>
</body>
</html>
