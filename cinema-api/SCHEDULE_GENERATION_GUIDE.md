# Tự động tạo suất chiếu cho phim

## Tổng quan

Hệ thống tự động tạo suất chiếu khi:
1. **Tạo phim mới** - Tự động tạo schedules cho 30 ngày từ ngày phát hành
2. **Cập nhật ngày phát hành** - Xóa schedules cũ và tạo mới

## Cách hoạt động

### 1. Tự động khi tạo/cập nhật phim

```php
// Trong MovieController@store
$scheduleService = new ScheduleGenerationService();
$schedules = $scheduleService->generateSchedulesForMovie($movie);

// Trong MovieController@update (khi có release_date)
$scheduleService = new ScheduleGenerationService();
$schedules = $scheduleService->regenerateSchedulesForMovie($movie);
```

### 2. Logic tạo schedules

**Dựa vào:**
- **Ngày phát hành** (`release_date`) của phim
- **Tất cả rạp đang hoạt động** (`is_active = true`)
- **Thời gian chiếu** khác nhau cho ngày thường vs cuối tuần
- **Giá vé** tính theo giờ, ngày, phòng

**Thời gian chiếu:**
- **Ngày thường (T2-T6)**: 14:00, 17:30, 20:00
- **Cuối tuần (T7-CN)**: 10:00, 14:00, 17:30, 20:00, 22:30

**Giá vé:**
- **Giá cơ bản**: 80,000 VND
- **Tối (sau 18h)**: +20,000 VND
- **Cuối tuần**: +10,000 VND
- **Phòng VIP**: +15,000 VND
- **Giờ cao điểm (19-21h)**: +10,000 VND

### 3. Khởi tạo ghế

Mỗi schedule sẽ tự động:
- Tạo `schedule_seats` cho tất cả ghế trong rạp
- Cập nhật `available_seats` trong bảng `schedules`

## API Endpoints

### 1. Tạo schedules cho phim cụ thể
```http
POST /api/movies/{id}/schedules/generate
Authorization: Bearer {token}
```

### 2. Tạo lại schedules (xóa cũ, tạo mới)
```http
POST /api/movies/{id}/schedules/regenerate
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Schedules generated successfully",
  "data": {
    "movie_id": 1,
    "movie_title": "Movie Title",
    "schedules_generated": 120,
    "schedules": [...]
  }
}
```

## Artisan Commands

### 1. Tạo schedules cho phim cụ thể
```bash
php artisan schedules:generate --movie=1
```

### 2. Tạo lại schedules cho phim cụ thể
```bash
php artisan schedules:generate --movie=1 --regenerate
```

### 3. Tạo schedules cho tất cả phim
```bash
php artisan schedules:generate --all
```

## Cấu trúc Database

### Bảng `schedules`
```sql
- id (primary key)
- movie_id (foreign key)
- theater_id (foreign key)
- room_name (varchar)
- start_time (datetime)
- end_time (datetime)
- price (decimal)
- available_seats (json)
- status (enum: active, inactive)
```

### Bảng `schedule_seats`
```sql
- id (primary key)
- schedule_id (foreign key)
- seat_id (foreign key)
- status (enum: available, reserved, sold)
- locked_until (datetime, nullable)
```

## Service Class

### `ScheduleGenerationService`

**Methods:**
- `generateSchedulesForMovie(Movie $movie)` - Tạo schedules cho phim
- `regenerateSchedulesForMovie(Movie $movie)` - Tạo lại schedules
- `generateSchedulesForAllMovies()` - Tạo cho tất cả phim

**Features:**
- Tự động skip schedules trong quá khứ
- Kiểm tra schedules đã tồn tại
- Khởi tạo ghế tự động
- Logging chi tiết
- Error handling

## Ví dụ sử dụng

### 1. Tạo phim mới
```php
$movie = Movie::create([
    'title' => 'Avengers: Endgame',
    'release_date' => '2025-01-20',
    'duration' => 180,
    // ... other fields
]);

// Schedules sẽ tự động được tạo cho 30 ngày từ 20/1/2025
```

### 2. Cập nhật ngày phát hành
```php
$movie = Movie::find(1);
$movie->update([
    'release_date' => '2025-02-01'
]);

// Schedules cũ sẽ bị xóa và tạo mới từ 1/2/2025
```

### 3. Sử dụng API
```javascript
// Tạo schedules cho phim ID 1
fetch('/api/movies/1/schedules/generate', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    }
})
.then(response => response.json())
.then(data => {
    console.log(`Generated ${data.data.schedules_generated} schedules`);
});
```

## Logs

Tất cả hoạt động được ghi log trong `storage/logs/laravel.log`:

```
[2025-01-15 10:30:00] local.INFO: Generating schedules for movie: 1 - Avengers: Endgame
[2025-01-15 10:30:01] local.INFO: Found 3 active theaters
[2025-01-15 10:30:01] local.INFO: Release date: 2025-01-20
[2025-01-15 10:30:01] local.INFO: End date: 2025-02-19
[2025-01-15 10:30:02] local.INFO: Processing theater: 1 - CGV Pandora City
[2025-01-15 10:30:02] local.INFO: Processing date: 2025-01-20
[2025-01-15 10:30:02] local.INFO: Creating schedule: 2025-01-20 14:00:00 - 2025-01-20 17:00:00
[2025-01-15 10:30:02] local.INFO: Schedule created successfully with ID: 123
```

## Troubleshooting

### 1. Không có schedules được tạo
- Kiểm tra có rạp nào `is_active = true` không
- Kiểm tra ngày phát hành có hợp lệ không
- Xem logs để biết lỗi cụ thể

### 2. Schedules bị trùng
- Service tự động skip schedules đã tồn tại
- Sử dụng `--regenerate` để xóa cũ và tạo mới

### 3. Ghế không được khởi tạo
- Kiểm tra bảng `seats` có dữ liệu không
- Kiểm tra `theater_id` trong `seats` có đúng không

## Cấu hình

### Thay đổi số ngày tạo schedules
```php
// Trong ScheduleGenerationService
$endDate = $releaseDate->copy()->addDays(30); // Thay đổi số 30
```

### Thay đổi thời gian chiếu
```php
// Trong ScheduleGenerationService::getScheduleTimes()
if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
    return [
        ['hour' => 14, 'minute' => 0],
        ['hour' => 17, 'minute' => 30],
        ['hour' => 20, 'minute' => 0],
    ];
}
```

### Thay đổi giá vé
```php
// Trong ScheduleGenerationService::calculatePrice()
$basePrice = 80000; // Thay đổi giá cơ bản
```
php artisan schedules:generate --all