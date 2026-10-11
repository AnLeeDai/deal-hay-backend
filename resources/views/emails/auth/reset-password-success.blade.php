<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mật khẩu của bạn đã được đặt lại thành công</title>
</head>
<body style="margin: 0; padding: 24px; font-family: Arial, sans-serif; background: #f5f5f5; color: #111827;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h1 style="margin: 0 0 16px; font-size: 24px; line-height: 1.3;">Xin chào {{ $fullName }},</h1>

        <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.7;">
            Mật khẩu tài khoản của bạn vừa được đặt lại thành công.
        </p>

        <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.7;">
            Dưới đây là thông tin tài khoản liên quan:
        </p>

        <div style="margin-bottom: 24px; padding: 20px; border-radius: 12px; background: #f9fafb; border: 1px solid #e5e7eb;">
            <p style="margin: 0;"><strong>Email:</strong> {{ $email }}</p>
        </div>

        <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.7;">
            Bạn có thể đăng nhập lại tại đây:
            <a href="{{ $signInUrl }}" style="color: #2563eb;">{{ $signInUrl }}</a>
        </p>

        <p style="margin: 0; font-size: 13px; line-height: 1.7; color: #6b7280;">
            Nếu bạn không thực hiện thao tác này, vui lòng đổi mật khẩu ngay và liên hệ hỗ trợ.
        </p>
    </div>
</body>
</html>