# API Endpoints — Cinema API

Tài liệu này liệt kê tất cả các endpoint có trong `API/cinema-api` (theo `routes/api.php`) — bao gồm phương thức, đường dẫn, yêu cầu xác thực, payload chính và mô tả ngắn.

> Ghi chú: server dùng JWT (`auth:api`) cho các route được bảo vệ. Thêm header `Authorization: Bearer <token>` khi endpoint yêu cầu.

---

## Authentication

- POST /api/auth/register
  - Mô tả: Bắt đầu đăng ký (gửi OTP). Trả về thông tin tạm.
  - Payload: { name, email, password, password_confirmation }
  - Auth: No

- POST /api/auth/login
  - Mô tả: Đăng nhập, trả `access_token` (JWT).
  - Payload: { email, password }
  - Auth: No
  - Response: { success, data: { user, access_token, token_type, expires_in } }

- POST /api/auth/send-otp
  - Mô tả: Gửi mã OTP (verification/reset)
  - Payload: { email, type? }
  - Auth: No

- POST /api/auth/verify-otp
  - Mô tả: Xác thực OTP; hoàn tất đăng ký, trả token
  - Payload: { email, otp, name, password, password_confirmation }
  - Auth: No

- POST /api/auth/forgot-password
  - Mô tả: Gửi OTP đặt lại mật khẩu
  - Payload: { email }
  - Auth: No

- POST /api/auth/reset-password
  - Mô tả: Đặt lại mật khẩu bằng OTP
  - Payload: { email, otp, password, password_confirmation }
  - Auth: No

- POST /api/auth/change-password
  - Mô tả: Đổi mật khẩu (user phải auth)
  - Payload: { current_password, password, password_confirmation }
  - Auth: Yes (`auth:api`)

- POST /api/auth/logout
  - Mô tả: Logout (invalidate token)
  - Auth: Yes

- POST /api/auth/refresh
  - Mô tả: Refresh JWT token
  - Auth: Yes

- GET /api/auth/me
  - Mô tả: Lấy thông tin user hiện tại
  - Auth: Yes

---

## Movies

- GET /api/movies
  - Mô tả: Lấy danh sách phim (filter, paging có thể được thêm)
  - Auth: No

- GET /api/movies/featured
  - Mô tả: Lấy phim nổi bật
  - Auth: No

- GET /api/movies/search
  - Mô tả: Tìm kiếm phim
  - Auth: No

- GET /api/movies/{id}
  - Mô tả: Chi tiết phim
  - Auth: No

- GET /api/movies/{id}/cast
  - Mô tả: Lấy cast cho phim
  - Auth: No

- PUT /api/movies/{id}/cast
  - Mô tả: Cập nhật cast (admin)
  - Auth: Yes (permission likely required)

- POST /api/movies (Admin)
- POST /api/movies/with-files (Admin)
- PUT /api/movies/{id} (Admin)
- DELETE /api/movies/{id} (Admin)
  - Mô tả: CRUD phim (yêu cầu auth + admin)

---

## Theaters

- GET /api/theaters
  - Mô tả: Danh sách rạp
  - Auth: No

- GET /api/theaters/{id}
  - Mô tả: Chi tiết rạp
  - Auth: No

- GET /api/theaters/{theaterId}/schedules
  - Mô tả: Lịch chiếu của rạp
  - Auth: No

- GET /api/theaters/{theaterId}/movies/{movieId}/schedules
  - Mô tả: Lịch chiếu cho phim tại rạp
  - Auth: No

- DELETE /api/theaters/{id}
  - Mô tả: Xóa rạp (admin)
  - Auth: Yes

- Admin theater endpoints under `/admin/theaters` (create/update/delete)

---

## Schedules (suất chiếu)

- GET /api/schedules
- GET /api/schedules/{id}
- GET /api/schedules/movie/{movieId}
- GET /api/schedules/movie/{movieId}/flutter
  - Mô tả: Phiên bản trả về cho Flutter (gọn hơn, theo ngày)
- GET /api/schedules/movie/{movieId}/dates
- GET /api/schedules/movie/{movieId}/dates/flutter
  - Mô tả: Ngày có lịch chiếu cho Flutter
- GET /api/schedules/date?date=YYYY-MM-DD
- POST /api/schedules (auth)
- PUT /api/schedules/{id} (auth)
- DELETE /api/schedules/{id} (auth)

- GET /api/schedules/{scheduleId}/seats
  - Mô tả: Trả danh sách ghế với trạng thái (available/reserved/sold). Dùng để hiển thị sơ đồ ghế.
  - Auth: No

---

## Booking (quan trọng)

- POST /api/bookings/lock-seats
  - Mô tả: Khóa ghế trước thanh toán
  - Auth: Yes (controller dùng middleware auth)
  - Payload example:
    {
      "schedule_id": 123,
      "seat_numbers": ["A1","A2"],
      "lock_duration_minutes": 10
    }
  - Success response data:
    {
      "locked_seats": ["A1","A2"],
      "seat_ids": [45,46],
      "locked_until": "2025-...",
      "expires_in_minutes": 10
    }

- POST /api/bookings/release-seats
  - Mô tả: Mở khóa ghế (khi hủy/ lỗi thanh toán)
  - Auth: Yes
  - Payload example:
    {
      "schedule_id": 123,
      "seat_ids": [45,46]
    }

- POST /api/bookings
  - Mô tả: Tạo booking (confirm); controller kiểm tra ghế đã được khóa và lock chưa hết hạn
  - Auth: Yes
  - Payload example (Flutter-friendly):
    {
      "showtime_id": 123,
      "seat_ids": ["3_3","3_4"],  // hoặc ids integers (web)
      "snacks": [{"snack_id":4,"quantity":2}],
      "total_price": 250000
    }
  - Response: success, data.booking_id
  - Hành vi: chuyển schedule_seat.status từ 'reserved' -> 'sold', tạo booking_seats, booking_snacks, sinh QR và gửi email (nếu có)

- GET /api/bookings/{bookingId}
- GET /api/users/{userId}/bookings
- POST /api/bookings/{bookingId}/cancel
  - Mô tả: Hủy booking (kiểm tra thời gian showtime, release ghế)

---

## Snacks

- GET /api/snacks
  - Mô tả: Lấy danh sách snacks (BookingController::getSnacks)
  - Auth: Public in routes, nhưng có thể tùy cấu hình middleware

---

## Reviews & Comments

- GET /api/movies/{movieId}/reviews
- POST /api/movies/{movieId}/reviews
- GET /api/movies/{movieId}/reviews/public
- GET /api/reviews/public
- GET /api/reviews/{id}
- PUT /api/reviews/{id} (auth)
- DELETE /api/reviews/{id} (auth)

- Comments: tương tự (store, index, reply endpoints). Reply endpoints public:
  - POST /api/comments/{id}/reply
  - POST /api/reviews/{id}/reply

---

## Favorites

- GET /api/favorites (auth)
- POST /api/favorites (auth)
- DELETE /api/favorites/{movieId} (auth)

---

## Users & Admin

- GET /api/users (admin/public for admin dashboard)
- GET /api/users/export
- POST /api/users (auth)
- GET /api/users/{id} (auth)
- GET /api/users/{id}/bookings (auth)
- GET /api/users/{id}/favorites (auth)
- POST /api/users/{id}/assign-role (admin)
- POST /api/users/{id}/toggle-status (admin)
- DELETE /api/users/{id} (admin)

---

## Roles & Permissions

- GET /api/permissions (auth)
- /api/roles prefix: GET/POST/PUT/DELETE to manage roles (auth/admin)

---

## Violations (content moderation)

- POST /api/violations/{id}/toggle-visibility
- /api/violations resource endpoints (auth)

---

## File upload & image proxy

- GET /api/upload/test-auth
- POST /api/upload/file (auth) — upload to Google Drive (avatar uses local storage)
- POST /api/upload/file-local — store image locally (no auth)
- POST /api/upload/files — upload multiple files
- DELETE /api/upload/file — delete file by URL
- GET /api/upload/file-info — extract file ID and download URL
- GET /api/image-proxy?url=<google-drive-url> — proxy image bytes (avoid CORS)

---

## Admin statistics (public endpoints for testing)

- GET /api/admin/statistics/movies
- GET /api/admin/statistics/users
- GET /api/admin/statistics/reviews
- GET /api/admin/statistics/bookings
- GET /api/admin/statistics/most-viewed-movies
- GET /api/admin/statistics/monthly-revenue

---


