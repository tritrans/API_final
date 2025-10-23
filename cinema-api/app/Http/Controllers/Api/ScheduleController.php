<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Movie;
use App\Models\Theater;
use App\Traits\ApiResponse;
use App\Enums\ErrorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    use ApiResponse;

    /**
     * Check if user has admin permissions
     */
    private function checkAdminPermissions()
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions');
        }

        $user->load('roles');
        $userRole = $user->roles->first()?->name ?? 'user';
        
        if (!in_array($userRole, ['admin', 'movie_manager', 'super_admin'])) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions');
        }

        return null; // No error
    }

    /**
     * Display a listing of schedules
     */
    public function index(Request $request)
    {
        $query = Schedule::with(['movie', 'theater']);

        // Filter by movie
        if ($request->has('movie_id')) {
            $query->where('movie_id', $request->movie_id);
        }

        // Filter by theater
        if ($request->has('theater_id')) {
            $query->where('theater_id', $request->theater_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('start_time', $request->date);
        }

        // Filter by upcoming schedules
        if ($request->has('upcoming') && $request->upcoming) {
            $query->where('start_time', '>', now());
        }

        $schedules = $query->orderBy('start_time')->get();

        // Calculate available seats for each schedule
        $schedules->transform(function ($schedule) {
            $schedule->available_seats = $this->calculateAvailableSeats($schedule);
            return $schedule;
        });

        return $this->successResponse($schedules, 'Schedules retrieved successfully');
    }

    /**
     * Calculate available seats for a schedule
     */
    private function calculateAvailableSeats($schedule)
    {
        // Get total seats for this theater
        $totalSeats = \App\Models\Seat::where('theater_id', $schedule->theater_id)->count();
        
        // Get booked seats for this schedule
        $bookedSeats = \App\Models\ScheduleSeat::where('schedule_id', $schedule->id)
            ->where('status', 'sold')
            ->count();
        
        // Calculate available seats
        $availableSeats = $totalSeats - $bookedSeats;
        
        return [
            'total_seats' => $totalSeats,
            'booked_seats' => $bookedSeats,
            'available_seats' => $availableSeats,
            'available_seat_list' => $this->getAvailableSeatList($schedule)
        ];
    }

    /**
     * Get list of available seats
     */
    private function getAvailableSeatList($schedule)
    {
        $bookedSeatIds = \App\Models\ScheduleSeat::where('schedule_id', $schedule->id)
            ->where('status', 'sold')
            ->pluck('seat_id')
            ->toArray();
        
        $availableSeats = \App\Models\Seat::where('theater_id', $schedule->theater_id)
            ->whereNotIn('id', $bookedSeatIds)
            ->get(['id', 'row_label', 'seat_number'])
            ->toArray();
        
        return $availableSeats;
    }

    /**
     * Display the specified schedule
     */
    public function show($id)
    {
        $schedule = Schedule::with(['movie', 'theater'])->find($id);
        
        if (!$schedule) {
            return $this->errorResponse(ErrorCode::SCHEDULE_NOT_FOUND, null, 'Schedule not found');
        }

        return $this->successResponse($schedule, 'Schedule retrieved successfully');
    }

    /**
     * Get schedules for a specific movie (Web app - original)
     */
    public function movieSchedules($movieId)
    {
        $movie = Movie::find($movieId);
        
        if (!$movie) {
            return $this->errorResponse(ErrorCode::MOVIE_NOT_FOUND, null, 'Movie not found');
        }

        // Get schedules for next 7 days only (like real apps)
        $startDate = now();
        $endDate = now()->addDays(7);

        $schedules = Schedule::with(['theater'])
            ->where('movie_id', $movieId)
            ->where('start_time', '>', $startDate)
            ->where('start_time', '<', $endDate)
            ->orderBy('start_time')
            ->get();

        // Calculate real seat data
        $formattedSchedules = $schedules->map(function ($schedule) {
            $seatData = $this->calculateAvailableSeats($schedule);
            
            return [
                'id' => $schedule->id,
                'movie_id' => $schedule->movie_id,
                'theater_id' => $schedule->theater_id,
                'room_name' => $schedule->room_name,
                'start_time' => $schedule->start_time->toISOString(),
                'end_time' => $schedule->end_time->toISOString(),
                'price' => $schedule->price,
                'status' => $schedule->status ?? 'active',
                'theater' => [
                    'id' => $schedule->theater->id,
                    'name' => $schedule->theater->name,
                    'address' => $schedule->theater->address,
                    'phone' => $schedule->theater->phone,
                    'email' => $schedule->theater->email,
                ],
                'available_seats' => $seatData['available_seats'],
                'total_seats' => $seatData['total_seats'],
                'booked_seats' => $seatData['booked_seats'],
            ];
        });

        return $this->successResponse($formattedSchedules, 'Movie schedules retrieved successfully');
    }

    /**
     * Get schedules for Flutter app (optimized by date)
     */
    public function movieSchedulesFlutter($movieId, Request $request)
    {
        $movie = Movie::find($movieId);
        
        if (!$movie) {
            return $this->errorResponse(ErrorCode::MOVIE_NOT_FOUND, null, 'Movie not found');
        }

        // Get date parameter, default to today
        $selectedDate = $request->get('date', now()->format('Y-m-d'));
        
        // Parse date and create start/end of day
        $startOfDay = \Carbon\Carbon::parse($selectedDate)->startOfDay();
        $endOfDay = \Carbon\Carbon::parse($selectedDate)->endOfDay();

        $schedules = Schedule::with(['theater'])
            ->where('movie_id', $movieId)
            ->where('start_time', '>=', $startOfDay)
            ->where('start_time', '<=', $endOfDay)
            ->orderBy('start_time')
            ->get();

        // Simple response for Flutter
        $formattedSchedules = $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'movie_id' => $schedule->movie_id,
                'theater_id' => $schedule->theater_id,
                'room_name' => $schedule->room_name,
                'start_time' => $schedule->start_time->toISOString(),
                'end_time' => $schedule->end_time->toISOString(),
                'price' => $schedule->price,
                'status' => $schedule->status ?? 'active',
                'theater' => [
                    'id' => $schedule->theater->id,
                    'name' => $schedule->theater->name,
                ],
                'available_seats' => 50, // Default for Flutter
                'total_seats' => 96, // Default for Flutter
            ];
        });

        return $this->successResponse($formattedSchedules, 'Movie schedules retrieved successfully');
    }

    /**
     * Get available dates for a movie (Flutter optimized)
     */
    public function movieAvailableDatesFlutter($movieId)
    {
        $movie = Movie::find($movieId);
        
        if (!$movie) {
            return $this->errorResponse(ErrorCode::MOVIE_NOT_FOUND, null, 'Movie not found');
        }

        // Get unique dates with schedules
        $dates = Schedule::where('movie_id', $movieId)
            ->where('start_time', '>=', now()->startOfDay())
            ->selectRaw('DATE(start_time) as date')
            ->distinct()
            ->orderBy('date')
            ->pluck('date')
            ->map(function ($date) {
                return [
                    'date' => $date,
                    'formatted' => \Carbon\Carbon::parse($date)->format('d/m'),
                    'day_name' => \Carbon\Carbon::parse($date)->format('l'),
                    'is_today' => \Carbon\Carbon::parse($date)->isToday(),
                ];
            });

        return $this->successResponse($dates, 'Available dates retrieved successfully');
    }

    /**
     * Get available dates for a movie
     */
    public function availableDates($movieId)
    {
        $movie = Movie::find($movieId);
        
        if (!$movie) {
            return $this->errorResponse(ErrorCode::MOVIE_NOT_FOUND, null, 'Movie not found');
        }

        $dates = Schedule::where('movie_id', $movieId)
            ->where('start_time', '>', now())
            ->selectRaw('DATE(start_time) as date')
            ->distinct()
            ->orderBy('date')
            ->pluck('date');

        return $this->successResponse($dates, 'Available dates retrieved successfully');
    }

    /**
     * Get schedules for a specific date
     */
    public function dateSchedules(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'movie_id' => 'nullable|exists:movies,id',
            'theater_id' => 'nullable|exists:theaters,id',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $query = Schedule::with(['movie', 'theater'])
            ->whereDate('start_time', $request->date);

        if ($request->has('movie_id')) {
            $query->where('movie_id', $request->movie_id);
        }

        if ($request->has('theater_id')) {
            $query->where('theater_id', $request->theater_id);
        }

        $schedules = $query->orderBy('start_time')->get();

        return $this->successResponse($schedules, 'Date schedules retrieved successfully');
    }

    /**
     * Store a newly created schedule
     */
    public function store(Request $request)
    {
        // Check permissions
        $permissionError = $this->checkAdminPermissions();
        if ($permissionError) {
            return $permissionError;
        }

        $validator = Validator::make($request->all(), [
            'movie_id' => 'required|exists:movies,id',
            'theater_id' => 'required|exists:theaters,id',
            'room_name' => 'required|string|max:255',
            'start_time' => 'required|date|after:now',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, $validator->errors(), 'Validation failed');
        }

        try {
            $movie = Movie::find($request->movie_id);
            if (!$movie) {
                return $this->errorResponse(ErrorCode::MOVIE_NOT_FOUND, null, 'Movie not found');
            }

            $theater = Theater::find($request->theater_id);
            if (!$theater) {
                return $this->errorResponse(ErrorCode::THEATER_NOT_FOUND, null, 'Theater not found');
            }

            // Calculate end time based on movie duration
            $startTime = Carbon::parse($request->start_time);
            $endTime = $startTime->copy()->addMinutes($movie->duration + 30); // Add 30 min buffer

            $schedule = Schedule::create([
                'movie_id' => $request->movie_id,
                'theater_id' => $request->theater_id,
                'room_name' => $request->room_name,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $request->price,
                'status' => $request->status ?? 'active',
                'available_seats' => json_encode([])
            ]);

            return $this->successResponse($schedule, 'Schedule created successfully');

        } catch (\Exception $e) {
            \Log::error('Schedule creation error: ' . $e->getMessage());
            return $this->errorResponse(ErrorCode::INTERNAL_ERROR, null, 'Failed to create schedule');
        }
    }

    /**
     * Update a schedule
     */
    public function update(Request $request, $id)
    {
        // Check permissions
        $permissionError = $this->checkAdminPermissions();
        if ($permissionError) {
            return $permissionError;
        }

        $validator = Validator::make($request->all(), [
            'movie_id' => 'required|exists:movies,id',
            'theater_id' => 'required|exists:theaters,id',
            'room_name' => 'required|string|max:255',
            'start_time' => 'required|date',
            'price' => 'required|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, $validator->errors(), 'Validation failed');
        }

        try {
            $schedule = Schedule::find($id);
            
            if (!$schedule) {
                return $this->errorResponse(ErrorCode::SCHEDULE_NOT_FOUND, null, 'Schedule not found');
            }

            $movie = Movie::find($request->movie_id);
            if (!$movie) {
                return $this->errorResponse(ErrorCode::MOVIE_NOT_FOUND, null, 'Movie not found');
            }

            $theater = Theater::find($request->theater_id);
            if (!$theater) {
                return $this->errorResponse(ErrorCode::THEATER_NOT_FOUND, null, 'Theater not found');
            }

            // Calculate end time based on movie duration
            $startTime = Carbon::parse($request->start_time);
            $endTime = $startTime->copy()->addMinutes($movie->duration + 30); // Add 30 min buffer

            $schedule->update([
                'movie_id' => $request->movie_id,
                'theater_id' => $request->theater_id,
                'room_name' => $request->room_name,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $request->price,
                'status' => $request->status ?? 'active'
            ]);

            return $this->successResponse($schedule, 'Schedule updated successfully');

        } catch (\Exception $e) {
            \Log::error('Schedule update error: ' . $e->getMessage());
            return $this->errorResponse(ErrorCode::INTERNAL_ERROR, null, 'Failed to update schedule');
        }
    }

    /**
     * Delete a schedule
     */
    public function destroy($id)
    {
        // Check permissions
        $permissionError = $this->checkAdminPermissions();
        if ($permissionError) {
            return $permissionError;
        }

        try {
            $schedule = Schedule::find($id);
            
            if (!$schedule) {
                return $this->errorResponse(ErrorCode::SCHEDULE_NOT_FOUND, null, 'Schedule not found');
            }

            // Check if schedule has bookings
            $hasBookings = \App\Models\Booking::where('schedule_id', $id)->exists();
            if ($hasBookings) {
                return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'Cannot delete schedule with existing bookings');
            }

            $schedule->delete();

            return $this->successResponse(null, 'Schedule deleted successfully');

        } catch (\Exception $e) {
            \Log::error('Schedule deletion error: ' . $e->getMessage());
            return $this->errorResponse(ErrorCode::INTERNAL_ERROR, null, 'Failed to delete schedule');
        }
    }
}
