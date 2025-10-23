<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Theater;
use App\Models\Schedule;
use App\Traits\ApiResponse;
use App\Enums\ErrorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TheaterController extends Controller
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
     * Display a listing of theaters
     */
    public function index()
    {
        $theaters = Theater::with('schedules.movie')->get();
        
        // Append schedule info and rooms with real data to each theater
        $theaters->transform(function ($theater) {
            $theater->append('schedule_info');
            
            // Get unique room names from schedules
            $roomNames = $theater->schedules->pluck('room_name')->unique()->filter()->values();
            
            // Create rooms array with real data
            $rooms = $roomNames->map(function ($roomName) use ($theater) {
                // Count seats for this specific room
                $seatCount = 0;
                
                // Try to map room name to row label
                $roomNumber = preg_replace('/[^0-9]/', '', $roomName);
                if ($roomNumber) {
                    $rowLabels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                    $rowIndex = (int)$roomNumber - 1;
                    if (isset($rowLabels[$rowIndex])) {
                        $seatCount = \App\Models\Seat::where('theater_id', $theater->id)
                            ->where('row_label', $rowLabels[$rowIndex])
                            ->count();
                    }
                }
                
                // If no mapping found, use realistic seat count based on room number
                if ($seatCount == 0) {
                    // Realistic seat counts for different room types
                    $roomSeatCounts = [
                        1 => 96,  // Phòng 1: 96 ghế (8 rows × 12 seats)
                        2 => 96,  // Phòng 2: 96 ghế
                        3 => 96,  // Phòng 3: 96 ghế
                        4 => 96,  // Phòng 4: 96 ghế
                        5 => 96,  // Phòng 5: 96 ghế
                        6 => 120, // Phòng 6: 120 ghế (VIP)
                        7 => 80,  // Phòng 7: 80 ghế
                        8 => 80,  // Phòng 8: 80 ghế
                    ];
                    
                    $seatCount = $roomSeatCounts[$roomNumber] ?? 96; // Default to 96
                }
                
                // Determine room type based on seat count
                $roomType = 'Standard';
                if ($seatCount >= 120) {
                    $roomType = 'VIP';
                } elseif ($seatCount >= 100) {
                    $roomType = 'Premium';
                } elseif ($seatCount >= 80) {
                    $roomType = 'Standard';
                } else {
                    $roomType = 'Small';
                }
                
                return [
                    'name' => $roomName,
                    'seat_count' => $seatCount,
                    'is_active' => true,
                    'type' => $roomType
                ];
            });
            
            // Add rooms data to theater
            $theater->rooms = $rooms;
            
            return $theater;
        });

        return $this->successResponse($theaters, 'Theaters retrieved successfully');
    }

    /**
     * Display the specified theater
     */
    public function show($id)
    {
        try {
            $theater = Theater::find($id);
            
            if (!$theater) {
                return $this->errorResponse(ErrorCode::THEATER_NOT_FOUND, null, 'Theater not found');
            }

            // Get basic theater data
            $theaterData = [
                'id' => $theater->id,
                'name' => $theater->name,
                'address' => $theater->address,
                'phone' => $theater->phone,
                'email' => $theater->email,
                'description' => $theater->description,
                'is_active' => $theater->is_active ?? true,
                'created_at' => $theater->created_at,
                'updated_at' => $theater->updated_at,
                'rooms' => []
            ];

            // Get unique rooms from schedules
            $schedules = Schedule::where('theater_id', $id)->get();
            $rooms = $schedules->groupBy('room_name')->map(function ($schedules, $roomName) {
                return [
                    'name' => $roomName,
                    'seat_count' => 50, // Default seat count
                    'is_active' => true,
                    'type' => 'Standard'
                ];
            })->values();
            
            $theaterData['rooms'] = $rooms;

            return $this->successResponse($theaterData, 'Theater retrieved successfully');
            
        } catch (\Exception $e) {
            \Log::error('Theater show error: ' . $e->getMessage());
            return $this->errorResponse(ErrorCode::INTERNAL_ERROR, null, 'Failed to retrieve theater');
        }
    }

    /**
     * Get schedules for a specific theater
     */
    public function schedules($theaterId)
    {
        $theater = Theater::find($theaterId);
        
        if (!$theater) {
            return $this->errorResponse(ErrorCode::THEATER_NOT_FOUND, null, 'Theater not found');
        }

        $schedules = Schedule::with('movie')
            ->where('theater_id', $theaterId)
            ->orderBy('start_time')
            ->get();

        return $this->successResponse($schedules, 'Schedules retrieved successfully');
    }

    /**
     * Get schedules for a specific movie in a theater
     */
    public function movieSchedules(Request $request, $theaterId, $movieId)
    {
        $theater = Theater::find($theaterId);
        
        if (!$theater) {
            return $this->errorResponse(ErrorCode::THEATER_NOT_FOUND, null, 'Theater not found');
        }

        $query = Schedule::with('movie')
            ->where('theater_id', $theaterId)
            ->where('movie_id', $movieId);

        // Filter by date if provided
        if ($request->has('date')) {
            $query->whereDate('start_time', $request->date);
        }

        // Only show upcoming schedules
        $query->where('start_time', '>', now());

        $schedules = $query->orderBy('start_time')->get();

        return $this->successResponse($schedules, 'Movie schedules retrieved successfully');
    }

    /**
     * Store a newly created theater
     */
    public function store(Request $request)
    {
        // Check permissions
        $permissionError = $this->checkAdminPermissions();
        if ($permissionError) {
            return $permissionError;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, $validator->errors(), 'Validation failed');
        }

        $theater = Theater::create([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true)
        ]);

        return $this->createdResponse($theater, 'Theater created successfully');
    }

    /**
     * Update the specified theater
     */
    public function update(Request $request, $id)
    {
        // Check permissions
        $permissionError = $this->checkAdminPermissions();
        if ($permissionError) {
            return $permissionError;
        }

        $theater = Theater::find($id);
        
        if (!$theater) {
            return $this->errorResponse(ErrorCode::THEATER_NOT_FOUND, null, 'Theater not found');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, $validator->errors(), 'Validation failed');
        }

        $theater->update([
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'email' => $request->email,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', $theater->is_active)
        ]);

        return $this->successResponse($theater, 'Theater updated successfully');
    }

    /**
     * Remove the specified theater
     */
    public function destroy($id)
    {
        // Check permissions
        $permissionError = $this->checkAdminPermissions();
        if ($permissionError) {
            return $permissionError;
        }

        $theater = Theater::find($id);
        
        if (!$theater) {
            return $this->errorResponse(ErrorCode::THEATER_NOT_FOUND, null, 'Theater not found');
        }

        // Check if theater has schedules
        $scheduleCount = Schedule::where('theater_id', $id)->count();
        if ($scheduleCount > 0) {
            return $this->errorResponse(ErrorCode::VALIDATION_ERROR, null, 'Cannot delete theater with existing schedules');
        }

        $theater->delete();

        return $this->successResponse(null, 'Theater deleted successfully');
    }

    /**
     * Get all theaters for admin management
     */
public function adminIndex()
    {
        // Check permissions
        $permissionError = $this->checkAdminPermissions();
        if ($permissionError) {
            return $permissionError;
        }

        // Simple query without complex relationships to avoid timeout
        $theaters = Theater::orderBy('created_at', 'desc')->get();
        
        // Add rooms data with real seat counts
        $theaters->transform(function ($theater) {
            // Get unique room names from schedules
            $roomNames = Schedule::where('theater_id', $theater->id)
                ->distinct()
                ->pluck('room_name')
                ->filter()
                ->values();
            
            // Create rooms array with real data
            $rooms = $roomNames->map(function ($roomName) use ($theater) {
                // Count seats for this specific room
                $seatCount = 0;
                
                // Try to map room name to row label
                $roomNumber = preg_replace('/[^0-9]/', '', $roomName);
                if ($roomNumber) {
                    $rowLabels = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
                    $rowIndex = (int)$roomNumber - 1;
                    if (isset($rowLabels[$rowIndex])) {
                        $seatCount = \App\Models\Seat::where('theater_id', $theater->id)
                            ->where('row_label', $rowLabels[$rowIndex])
                            ->count();
                    }
                }
                
                // If no mapping found, use realistic seat count based on room number
                if ($seatCount == 0) {
                    // Realistic seat counts for different room types
                    $roomSeatCounts = [
                        1 => 96,  // Phòng 1: 96 ghế (8 rows × 12 seats)
                        2 => 96,  // Phòng 2: 96 ghế
                        3 => 96,  // Phòng 3: 96 ghế
                        4 => 96,  // Phòng 4: 96 ghế
                        5 => 96,  // Phòng 5: 96 ghế
                        6 => 120, // Phòng 6: 120 ghế (VIP)
                        7 => 80,  // Phòng 7: 80 ghế
                        8 => 80,  // Phòng 8: 80 ghế
                    ];
                    
                    $seatCount = $roomSeatCounts[$roomNumber] ?? 96; // Default to 96
                }
                
                // Determine room type based on seat count
                $roomType = 'Standard';
                if ($seatCount >= 120) {
                    $roomType = 'VIP';
                } elseif ($seatCount >= 100) {
                    $roomType = 'Premium';
                } elseif ($seatCount >= 80) {
                    $roomType = 'Standard';
                } else {
                    $roomType = 'Small';
                }
                
                return [
                    'name' => $roomName,
                    'seat_count' => $seatCount,
                    'is_active' => true,
                    'type' => $roomType
                ];
            });
            
            // Add rooms data to theater
            $theater->rooms = $rooms;
            
            return $theater;
        });
        
        return $this->successResponse($theaters, 'Theaters retrieved successfully');
    }
}
