<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực thay đổi email</title>
</head>
<body style="margin: 0; padding: 24px; font-family: Arial, sans-serif; background: #f5f5f5; color: #111827;">
    <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; padding: 32px; border: 1px solid #e5e7eb;">
        <h1 style="margin: 0 0 16px; font-size: 24px; line-height: 1.3;">Xác thực thay đổi email</h1>

        <p style="margin: 0 0 12px; font-size: 15px; line-height: 1.7;">
            Xin chào {{ $fullName }},
        </p>

        <p style="margin: 0 0 20px; font-size: 15px; line-height: 1.7;">
            Hệ thống vừa nhận yêu cầu đổi email từ <strong>{{ $currentEmail }}</strong> sang <strong>{{ $newEmail }}</strong>.
        </p>

        <div style="margin-bottom: 24px; padding: 20px; border-radius: 12px; background: #f9fafb; border: 1px solid #e5e7eb; text-align: center;">
            <p style="margin: 0 0 8px; font-size: 13px; color: #6b7280;">Mã xác thực của bạn</p>
            <p style="margin: 0; font-size: 32px; letter-spacing: 8px; font-weight: 700;">{{ $verificationCode }}</p>
            <p style="margin: 12px 0 0; font-size: 13px; color: #6b7280;">Mã có hiệu lực trong {{ $expiresInMinutes }} phút.</p>
        </div>

        <p style="margin: 0; font-size: 13px; line-height: 1.7; color: #6b7280;">
            Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email và cân nhắc đổi mật khẩu để bảo vệ tài khoản.
        </p>
    </div>
</body>
</html>
