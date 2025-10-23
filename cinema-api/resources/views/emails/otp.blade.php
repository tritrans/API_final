<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mã xác thực OTP</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .otp-box {
            background-color: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            display: inline-block;
        }
        .otp-code {
            font-size: 32px;
            font-weight: bold;
            color: #dc2626;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }
        .message {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Rạp Chiếu Phim</h1>
        </div>
        
        <div class="content">
            <h2>Mã xác thực OTP</h2>
            <p class="message">
                Xin chào <strong>{{ $userName ?? 'Bạn' }}</strong>!<br>
                Bạn đã yêu cầu mã xác thực để hoàn tất quá trình đăng ký tài khoản.
            </p>
            
            <div class="otp-box">
                <div class="otp-code">{{ $otp }}</div>
            </div>
            
            <p class="message">
                Mã này sẽ hết hạn sau <strong>5 phút</strong>.<br>
                Vui lòng không chia sẻ mã này với bất kỳ ai.
            </p>
            
            <div class="warning">
                 <strong>Lưu ý:</strong> Nếu bạn không thực hiện yêu cầu này, 
                vui lòng bỏ qua email này hoặc liên hệ hỗ trợ.
            </div>
        </div>
        
        <div class="footer">
            <p>© 2025 Rạp Chiếu Phim. Tất cả quyền được bảo lưu.</p>
            <p>Email này được gửi đến: {{ $email }}</p>
        </div>
    </div>
</body>
</html>