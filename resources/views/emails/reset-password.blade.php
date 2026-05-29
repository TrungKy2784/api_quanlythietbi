<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body>
    <h2>Yêu cầu đặt lại mật khẩu</h2>
    <p>Bạn nhận được email này vì chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
    <p>Vui lòng nhấp vào liên kết sau để đặt lại mật khẩu:</p>
    <a href="{{ env('FRONTEND_URL') }}/reset-password?token={{ $token }}&email={{ urlencode($email) }}">
        Đặt lại mật khẩu
    </a>
    <p>Liên kết này sẽ hết hạn sau 60 phút.</p>
    <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
</body>
</html>