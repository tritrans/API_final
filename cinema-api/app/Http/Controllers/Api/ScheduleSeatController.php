<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleSeatController extends Controller
{
    // Get all seats with status for a schedule
    public function index($scheduleId)
    {
        $schedule = Schedule::findOrFail($scheduleId);

        $seats = DB::table('seats')
            ->where('theater_id', $schedule->theater_id)
            ->leftJoin('schedule_seats', function ($join) use ($scheduleId) {
                $join->on('seats.id', '=', 'schedule_seats.seat_id')
                    ->where('schedule_seats.schedule_id', '=', $scheduleId);
            })
            ->select('seats.id as seat_id', 'seats.row_label', 'seats.seat_number', DB::raw("COALESCE(schedule_seats.status, 'available') AS status"))
            ->orderBy('row_label')
            ->orderBy('seat_number')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $seats,
        ]);
    }

    // Removed hold() and confirm() methods to prevent race conditions
    // Only use lockSeats -> createBooking flow in BookingController
}

