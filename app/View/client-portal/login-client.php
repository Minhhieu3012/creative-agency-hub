<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Client Login - Creative Agency Hub</title>
    <link href="../../../public/assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #4fc3f7, #1565c0);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-wrapper {
            width: 100%;
            max-width: 360px;
            padding: 20px;
        }

        /* Logo trên cùng */
        .logo-top {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-top span {
            background: white;
            color: #1565c0;
            font-weight: 800;
            font-size: 22px;
            padding: 10px 30px;
            border-radius: 30px;
            letter-spacing: 2px;
        }

        /* Card trắng */
        .login-card {
            background: white;
            border-radius: 20px;
            padding: 40px 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }

        .login-card h2 {
            text-align: center;
            color: #1565c0;
            font-weight: 800;
            font-size: 26px;
            letter-spacing: 2px;
            margin-bottom: 30px;
        }

        /* Input */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            color: #888;
            font-size: 13px;
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            border: none;
            border-bottom: 2px solid #e0e0e0;
            padding: 10px 0;
            font-size: 15px;
            color: #333;
            outline: none;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-bottom-color: #1565c0;
        }

        /* Forgot password */
        .forgot {
            text-align: right;
            margin-top: -10px;
            margin-bottom: 25px;
        }
        .forgot a {
            color: #888;
            font-size: 12px;
            text-decoration: none;
        }
        .forgot a:hover { color: #1565c0; }

        /* Nút login */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4fc3f7, #1565c0);
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 2px;
            cursor: pointer;
            transition: opacity 0.3s;
        }
        .btn-login:hover { opacity: 0.9; }

        /* Link dưới */
        .bottom-link {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #888;
        }
        .bottom-link a {
            color: #1565c0;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="login-wrapper">


        <!-- Card -->
        <div class="login-card">
            <h2>ĐĂNG NHẬP</h2>

            <form action="auth-client.php" method="POST">

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" 
                           placeholder="example@email.com" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" 
                           placeholder="••••••••" required>
                </div>

                <div class="forgot">
                    <a href="#">Quên mật khẩu?</a>
                </div>

                <button type="submit" class="btn-login">LOGIN</button>

            </form>

            <div class="bottom-link">
                Nhân viên? <a href="login.php">Đăng nhập nội bộ</a>
            </div>
        </div>

    </div>

    <script src="../../../public/assets/vendor/jquery/jquery.min.js"></script>
    <script src="../../../public/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
