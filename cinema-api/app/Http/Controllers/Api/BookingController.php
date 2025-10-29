<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSeat;
use App\Models\BookingSnack;
use App\Models\Schedule;
use App\Models\ScheduleSeat;
use App\Models\Seat;
use App\Models\Snack;
use App\Mail\BookingConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    // Lock seats before payment
    public function lockSeats(Request $request)
    {
        try {
            $request->validate([
                'schedule_id' => 'required|exists:schedules,id',
                'seat_numbers' => 'required|array|min:1',
                'seat_numbers.*' => 'required|string',
                'lock_duration_minutes' => 'integer|min:5|max:15',
            ]);

            $schedule = Schedule::findOrFail($request->schedule_id);
            $lockDuration = $request->lock_duration_minutes ?? 10; // Default 10 minutes
            $lockUntil = now()->addMinutes($lockDuration);

            DB::beginTransaction();

            try {
                $lockedSeats = [];
                $seatIds = [];

                foreach ($request->seat_numbers as $seatNumber) {
                    // Parse seat number (e.g., "A1" -> row "A", number 1)
                    $rowLabel = substr($seatNumber, 0, 1);
                    $seatNum = (int) substr($seatNumber, 1);

                    // Debug: Log seat lookup

                    // Find seat in database
                    $seat = Seat::where('theater_id', $schedule->theater_id)
                        ->where('row_label', $rowLabel)
                        ->where('seat_number', $seatNum)
                        ->first();

                    if (!$seat) {
                        throw new \Exception("Seat {$seatNumber} not found");
                    }


                    // Check if seat is available or can be locked
                    $scheduleSeat = ScheduleSeat::firstOrNew([
                        'schedule_id' => $schedule->id,
                        'seat_id' => $seat->id,
                    ]);


                    if ($scheduleSeat->exists && $scheduleSeat->status === 'sold') {
                        throw new \Exception("Seat {$seatNumber} is already sold");
                    }

                    if ($scheduleSeat->exists && $scheduleSeat->status === 'reserved' && 
                        $scheduleSeat->locked_until && $scheduleSeat->locked_until->isFuture()) {
                        throw new \Exception("Seat {$seatNumber} is already reserved");
                    }

                    // Lock the seat
                    $scheduleSeat->status = 'reserved';
                    $scheduleSeat->locked_until = $lockUntil;
                    $scheduleSeat->save();

                    $lockedSeats[] = $seatNumber;
                    $seatIds[] = $seat->id;
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Seats locked successfully',
                    'data' => [
                        'locked_seats' => $lockedSeats,
                        'seat_ids' => $seatIds,
                        'locked_until' => $lockUntil->toISOString(),
                        'expires_in_minutes' => $lockDuration,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to lock seats: ' . $e->getMessage()
            ], 400);
        }
    }

    // Release locked seats (if payment fails or user cancels)
    public function releaseSeats(Request $request)
    {
        try {
            $request->validate([
                'schedule_id' => 'required|exists:schedules,id',
                'seat_ids' => 'required|array|min:1',
                'seat_ids.*' => 'required|integer|exists:seats,id',
            ]);

            DB::beginTransaction();

            try {
                foreach ($request->seat_ids as $seatId) {
                    $scheduleSeat = ScheduleSeat::where('schedule_id', $request->schedule_id)
                        ->where('seat_id', $seatId)
                        ->where('status', 'reserved')
                        ->first();

                    if ($scheduleSeat) {
                        $scheduleSeat->status = 'available';
                        $scheduleSeat->locked_until = null;
                        $scheduleSeat->save();
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Seats released successfully'
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to release seats: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createBooking(Request $request)
    {
        try {
            $request->validate([
                'showtime_id' => 'required|exists:schedules,id',
                'seat_ids' => 'required|array|min:1',
                'seat_ids.*' => 'required', // Accept both string and integer
                'snacks' => 'array',
                'snacks.*.snack_id' => 'required|integer|exists:snacks,id',
                'snacks.*.quantity' => 'required|integer|min:1',
                'total_price' => 'required|numeric|min:0',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }


            DB::beginTransaction();

            try {
                // Convert seat_ids to actual seat IDs (support both integer and string formats)
                $actualSeatIds = [];
                foreach ($request->seat_ids as $seatIdValue) {
                    if (is_numeric($seatIdValue)) {
                        // Web format: direct seat ID (integer)
                        $actualSeatIds[] = (int) $seatIdValue;
                    } else {
                        // Flutter format: string format (e.g., "3_3" -> row 3, seat 3)
                        $parts = explode('_', $seatIdValue);
                        if (count($parts) !== 2) {
                            throw new \Exception("Invalid seat ID format: {$seatIdValue}");
                        }
                        
                        $rowLabel = $parts[0];
                        $seatNumber = (int) $parts[1];
                        
                        // Find the actual seat in database
                        $schedule = Schedule::find($request->showtime_id);
                        if (!$schedule) {
                            throw new \Exception("Schedule not found: {$request->showtime_id}");
                        }
                        
                        $seat = Seat::where('theater_id', $schedule->theater_id)
                            ->where('row_label', $rowLabel)
                            ->where('seat_number', $seatNumber)
                            ->first();
                        
                        if (!$seat) {
                            throw new \Exception("Seat not found: {$seatIdValue} in theater {$schedule->theater_id}");
                        }
                        
                        $actualSeatIds[] = $seat->id;
                    }
                }

                // Verify that all seats are locked and available for confirmation
                foreach ($actualSeatIds as $seatId) {
                    $scheduleSeat = ScheduleSeat::where('schedule_id', $request->showtime_id)
                        ->where('seat_id', $seatId)
                        ->first();

                    if (!$scheduleSeat || $scheduleSeat->status !== 'reserved') {
                        throw new \Exception("Seat ID {$seatId} is not properly locked for booking");
                    }

                    if ($scheduleSeat->locked_until && $scheduleSeat->locked_until->isPast()) {
                        throw new \Exception("Seat ID {$seatId} lock has expired");
                    }
                }

                // Generate unique booking ID
                $bookingId = 'BK' . strtoupper(Str::random(8));

                // Create booking
                $booking = Booking::create([
                    'booking_id' => $bookingId,
                    'user_id' => $user->id,
                    'showtime_id' => $request->showtime_id,
                    'total_price' => $request->total_price,
                    'status' => 'confirmed',
                ]);

                // Confirm seats (mark as sold) and create booking seats
                foreach ($actualSeatIds as $seatId) {
                    // Confirm the seat in schedule_seats table
                    $scheduleSeat = ScheduleSeat::where('schedule_id', $request->showtime_id)
                        ->where('seat_id', $seatId)
                        ->first();
                    
                    if (!$scheduleSeat) {
                        throw new \Exception("Seat ID {$seatId} is not properly locked for booking");
                    }
                    
                    if ($scheduleSeat->status !== 'reserved') {
                        throw new \Exception("Seat ID {$seatId} is not properly locked for booking");
                    }
                    
                    $scheduleSeat->status = 'sold';
                    $scheduleSeat->locked_until = null;
                    $scheduleSeat->save();

                    // Get seat details for booking_seats table
                    $seat = Seat::find($seatId);
                    
                    BookingSeat::create([
                        'booking_id' => $booking->id,
                        'seat_number' => $seat->row_label . $seat->seat_number,
                        'seat_type' => 'standard', // Default seat type
                        'price' => $request->total_price / count($actualSeatIds), // Distribute price evenly
                    ]);
                }

                // Create booking snacks
                if (!empty($request->snacks)) {
                    foreach ($request->snacks as $snackData) {
                        $snack = Snack::find($snackData['snack_id']);
                        $unitPrice = $snack->price;
                        $totalPrice = $unitPrice * $snackData['quantity'];

                        BookingSnack::create([
                            'booking_id' => $booking->id,
                            'snack_id' => $snackData['snack_id'],
                            'quantity' => $snackData['quantity'],
                            'unit_price' => $unitPrice,
                            'total_price' => $totalPrice,
                        ]);
                    }
                }

                DB::commit();

                // Generate QR code for booking
                $qrCodeBase64 = $this->generateSimpleQRCode($booking->booking_id);
                
                // Save QR code to file for email attachment
                $qrCodeFilename = 'qr_' . $booking->booking_id . '.png';
                $qrCodePath = storage_path('app/public/qr_codes/' . $qrCodeFilename);
                
                // Ensure directory exists
                if (!file_exists(dirname($qrCodePath))) {
                    mkdir(dirname($qrCodePath), 0755, true);
                }
                
                // Save QR code file
                file_put_contents($qrCodePath, base64_decode($qrCodeBase64));

                // Send booking confirmation email
                try {
                    Mail::to($user->email)->send(new BookingConfirmationMail($user, $booking, $qrCodeBase64));
                } catch (\Exception $e) {
                    // Log email error but don't fail the booking
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Booking created successfully',
                    'data' => [
                        'booking_id' => $booking->booking_id,
                        'total_price' => $booking->total_price,
                        'status' => $booking->status,
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getBookingDetails($bookingId)
    {
        try {
            $booking = Booking::with([
                'user', 
                'showtime.theater', 
                'showtime.movie',
                'seats',
                'snacks.snack'
            ])
                ->where('booking_id', $bookingId)
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'booking_id' => $booking->booking_id,
                    'user' => [
                        'name' => $booking->user->name,
                        'email' => $booking->user->email,
                    ],
                    'movie' => [
                        'title' => $booking->showtime->movie->title,
                        'poster' => $booking->showtime->movie->poster,
                    ],
                    'theater' => [
                        'name' => $booking->showtime->theater->name,
                        'address' => $booking->showtime->theater->address,
                    ],
                    'showtime' => [
                        'date' => $booking->showtime->date,
                        'start_time' => $booking->showtime->start_time,
                        'end_time' => $booking->showtime->end_time,
                        'format' => $booking->showtime->format,
                        'room_name' => $booking->showtime->room_name,
                    ],
                    'seats' => $booking->seats->map(function ($seat) {
                        return [
                            'seat_number' => $seat->seat_number,
                            'seat_type' => $seat->seat_type,
                            'price' => $seat->price,
                        ];
                    }),
                    'snacks' => $booking->snacks->map(function ($bookingSnack) {
                        return [
                            'snack' => [
                                'id' => $bookingSnack->snack->id,
                                'name' => $bookingSnack->snack->name,
                                'name_vi' => $bookingSnack->snack->name_vi,
                                'image' => $bookingSnack->snack->image,
                            ],
                            'quantity' => $bookingSnack->quantity,
                            'unit_price' => $bookingSnack->unit_price,
                            'total_price' => $bookingSnack->total_price,
                        ];
                    }),
                    'total_price' => $booking->total_price,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get booking details: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getUserBookings($userId)
    {
        try {
            $bookings = Booking::with([
                'showtime.theater', 
                'showtime.movie',
                'seats',
                'snacks.snack'
            ])
                ->where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->get();

            $bookingsData = $bookings->map(function ($booking) {
                return [
                    'booking_id' => $booking->booking_id,
                    'movie' => [
                        'title' => $booking->showtime?->movie?->title ?? 'N/A',
                        'poster' => $booking->showtime?->movie?->poster ?? '',
                    ],
                    'theater' => [
                        'name' => $booking->showtime?->theater?->name ?? 'N/A',
                    ],
                    'showtime' => [
                        'date' => $booking->showtime?->start_time ? date('Y-m-d', strtotime($booking->showtime->start_time)) : null,
                        'start_time' => $booking->showtime?->start_time ?? null,
                        'end_time' => $booking->showtime?->end_time ?? null,
                        'room_name' => $booking->showtime?->room_name ?? 'N/A',
                    ],
                    'seats' => $booking->seats->map(function ($seat) {
                        return [
                            'seat_number' => $seat->seat_number,
                            'seat_type' => $seat->seat_type,
                            'price' => $seat->price,
                        ];
                    }),
                    'snacks' => $booking->snacks->map(function ($bookingSnack) {
                        return [
                            'snack' => [
                                'name' => $bookingSnack->snack?->name ?? 'N/A',
                                'name_vi' => $bookingSnack->snack?->name_vi ?? 'N/A',
                            ],
                            'quantity' => $bookingSnack->quantity,
                            'total_price' => $bookingSnack->total_price,
                        ];
                    }),
                    'total_price' => $booking->total_price,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $bookingsData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get user bookings: ' . $e->getMessage()
            ], 500);
        }
    }

    public function cancelBooking($bookingId)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $booking = Booking::where('booking_id', $bookingId)
                ->where('user_id', $user->id)
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            // Check if booking can be cancelled (not already cancelled and showtime not passed)
            if ($booking->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking already cancelled'
                ], 400);
            }

            if ($booking->showtime && new \DateTime($booking->showtime->start_time) <= new \DateTime()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel booking after showtime has started'
                ], 400);
            }

            DB::beginTransaction();

            try {
                // Update booking status
                $booking->update(['status' => 'cancelled']);

                // Release seats back to available
                if ($booking->seats) {
                    foreach ($booking->seats as $bookingSeat) {
                        // Find the seat in schedule_seats table and mark as available
                        $rowLabel = substr($bookingSeat->seat_number, 0, 1);
                        $seatNumber = substr($bookingSeat->seat_number, 1);
                        
                        $scheduleSeat = \App\Models\ScheduleSeat::where('schedule_id', $booking->showtime_id)
                            ->whereHas('seat', function($query) use ($rowLabel, $seatNumber, $booking) {
                                $query->where('row_label', $rowLabel)
                                      ->where('seat_number', $seatNumber)
                                      ->where('theater_id', $booking->showtime->theater_id ?? null);
                            })
                            ->first();
                            
                        if ($scheduleSeat) {
                            $scheduleSeat->update([
                                'status' => 'available',
                                'locked_until' => null
                            ]);
                        }
                    }
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Booking cancelled successfully',
                    'data' => [
                        'booking_id' => $booking->booking_id,
                        'status' => $booking->status
                    ]
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getSnacks()
    {
        try {
            $snacks = Snack::where('available', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $snacks
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get snacks: ' . $e->getMessage()
            ], 500);
        }
    }

    // Simple QR code generator using QR Server API
    private function generateSimpleQRCode($data)
    {
        try {
            // Use QR Server API (free, no authentication required)
            $url = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($data);
            
            // Get QR code image using cURL for better error handling
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $qrCodeImage = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($qrCodeImage !== false && $httpCode === 200) {
                return base64_encode($qrCodeImage);
            } else {
                // Fallback: return a simple text representation
                return base64_encode('QR_CODE_' . $data);
            }
        } catch (\Exception $e) {
            // Fallback: return booking ID as text
            return base64_encode('BOOKING_ID_' . $data);
        }
    }
}
