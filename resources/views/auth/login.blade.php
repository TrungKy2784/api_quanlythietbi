<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Login Page</h1>
    <form action="{{ route('login') }}" method="POST">
        @csrf
        <label for="TenTaiKhoan">Tên Tài Khoản:</label>
        <input type="text" name="TenTaiKhoan" id="TenTaiKhoan" required><br>

        <label for="MatKhau">Mật Khẩu:</label>
        <input type="password" name="MatKhau" id="MatKhau" required><br>

        <button type="submit">Login</button>
    </form>
</body>
</html>
