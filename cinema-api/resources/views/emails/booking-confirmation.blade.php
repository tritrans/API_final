<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận đặt vé thành công</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: bold;
        }
        .header p {
            margin: 10px 0 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .booking-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #e74c3c;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            min-width: 120px;
        }
        .info-value {
            color: #34495e;
            text-align: right;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .qr-code {
            max-width: 200px;
            height: auto;
            border: 2px solid #e74c3c;
            border-radius: 8px;
            padding: 10px;
            background-color: white;
        }
        .qr-text {
            margin-top: 15px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .instructions {
            background-color: #e8f5e8;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #27ae60;
        }
        .instructions h3 {
            margin-top: 0;
            color: #27ae60;
        }
        .instructions ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin-bottom: 8px;
        }
        .footer {
            background-color: #2c3e50;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 14px;
        }
        .footer a {
            color: #3498db;
            text-decoration: none;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-confirmed {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Xác nhận đặt vé thành công!</h1>
            <p>Cinema Booking System</p>
        </div>
        
        <div class="content">
            <div class="greeting">
                Xin chào <strong>{{ $user->name }}</strong>,
            </div>
            
            <p>Cảm ơn bạn đã đặt vé tại hệ thống rạp chiếu phim của chúng tôi. Đơn đặt vé của bạn đã được xác nhận thành công!</p>
            
            <div class="booking-info">
                <h3 style="margin-top: 0; color: #e74c3c;">📋 Thông tin đặt vé</h3>
                
                <div class="info-row">
                    <span class="info-label">Mã đặt vé:</span>
                    <span class="info-value"><strong>{{ $booking->booking_id }}</strong></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Phim:</span>
                    <span class="info-value">{{ $booking->showtime->movie->title }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Rạp chiếu:</span>
                    <span class="info-value">{{ $booking->showtime->theater->name }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Phòng chiếu:</span>
                    <span class="info-value">{{ $booking->showtime->room_name }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Ngày chiếu:</span>
                    <span class="info-value">{{ \Carbon\Carbon::parse($booking->showtime->start_time)->format('d/m/Y') }}</span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Giờ chiếu:</span>
                    <span class="info-value">
                        {{ \Carbon\Carbon::parse($booking->showtime->start_time)->format('H:i') }} - 
                        {{ \Carbon\Carbon::parse($booking->showtime->end_time)->format('H:i') }}
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Ghế ngồi:</span>
                    <span class="info-value">
                        @foreach($booking->seats as $seat)
                            {{ $seat->seat_number }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </span>
                </div>
                
                @if($booking->snacks->count() > 0)
                <div class="info-row">
                    <span class="info-label">Đồ ăn:</span>
                    <span class="info-value">
                        @foreach($booking->snacks as $bookingSnack)
                            {{ $bookingSnack->snack->name_vi }} x{{ $bookingSnack->quantity }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </span>
                </div>
                @endif
                
                <div class="info-row">
                    <span class="info-label">Tổng tiền:</span>
                    <span class="info-value"><strong>{{ number_format($booking->total_price, 0, ',', '.') }} VNĐ</strong></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Trạng thái:</span>
                    <span class="info-value">
                        <span class="status-badge status-confirmed">Đã xác nhận</span>
                    </span>
                </div>
            </div>
            
            <div class="qr-section">
                <h3 style="margin-top: 0; color: #e74c3c;">📱 Mã QR vé</h3>
                <img src="data:image/png;base64,{{ $qrCode }}" alt="QR Code" class="qr-code">
                <div class="qr-text">
                    <strong>Mã đặt vé: {{ $booking->booking_id }}</strong><br>
                    Xuất trình mã QR này tại quầy để nhận vé
                </div>
            </div>
            
            <div class="instructions">
                <h3> Hướng dẫn sử dụng vé</h3>
                <ul>
                    <li><strong>Đến rạp trước 15 phút</strong> để có thời gian nhận vé và tìm chỗ ngồi</li>
                    <li><strong>Xuất trình mã QR</strong> tại quầy vé để nhận vé giấy</li>
                    <li><strong>Giữ vé cẩn thận</strong> trong suốt buổi chiếu</li>
                    <li><strong>Liên hệ hotline</strong> nếu có bất kỳ vấn đề gì</li>
                </ul>
            </div>
            
            <p style="text-align: center; margin-top: 30px;">
                <strong>Chúc bạn xem phim vui vẻ! </strong>
            </p>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Cinema Booking System. Tất cả quyền được bảo lưu.</p>
            <p>Nếu bạn có bất kỳ câu hỏi nào, vui lòng liên hệ: <a href="mailto:support@cinema.com">support@cinema.com</a></p>
        </div>
    </div>
</body>
</html>
