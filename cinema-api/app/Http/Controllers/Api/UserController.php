<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Enums\ErrorCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "User Management")]
class UserController extends Controller
{
    use ApiResponse;

    /**
     * Get all users for admin dashboard (public endpoint)
     */
    public function getAllUsersForAdmin()
    {
        try {
            $users = User::with(['roles'])->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($user) {
                    // Get primary role (first role or 'user' if no roles)
                    $primaryRole = $user->roles->first()?->name ?? 'user';
                    
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $primaryRole,
                        'role_id' => $user->role_id ?? 1,
                        'roles' => $user->roles->pluck('name')->toArray(),
                        'avatar' => $user->avatar,
                        'is_active' => $user->is_active ?? true,
                        'created_at' => $user->created_at->toISOString(),
                        'updated_at' => $user->updated_at->toISOString(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        } catch (\Exception $e) {return response()->json([
                'success' => false,
                'message' => 'Error retrieving users: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    #[OA\Get(
        path: "/api/users",
        summary: "Get all users",
        description: "Get paginated list of users (Admin only)",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Page number",
                required: false,
                schema: new OA\Schema(type: "integer", example: 1)
            ),
            new OA\Parameter(
                name: "per_page",
                in: "query",
                description: "Items per page",
                required: false,
                schema: new OA\Schema(type: "integer", example: 15)
            ),
            new OA\Parameter(
                name: "search",
                in: "query",
                description: "Search by name or email",
                required: false,
                schema: new OA\Schema(type: "string", example: "john")
            ),
            new OA\Parameter(
                name: "role",
                in: "query",
                description: "Filter by role",
                required: false,
                schema: new OA\Schema(type: "string", example: "user")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Users retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Users retrieved successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            )
        ]
    )]
    public function index(Request $request)
    {
        if (!auth()->user()->can('view users')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to view users');
        }

        $query = User::with('roles');

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        // Sort by created_at desc
        $query->orderBy('created_at', 'desc');

        $users = $query->paginate($request->get('per_page', 15));

        return $this->successResponse($users, 'Users retrieved successfully');
    }

    #[OA\Get(
        path: "/api/users/{id}",
        summary: "Get user profile",
        description: "Get detailed profile of a specific user",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User profile retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "User profile retrieved successfully"),
                        new OA\Property(property: "data", type: "object")
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            ),
            new OA\Response(
                response: 404,
                description: "User not found"
            )
        ]
    )]
    public function show($id)
    {
        $user = auth()->user();
        
        // Users can view their own profile, admins can view any profile
        if ($user->id != $id && $user->role !== 'admin') {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to view this user');
        }

        $targetUser = User::findOrFail($id);

        return $this->successResponse($targetUser, 'User profile retrieved successfully');
    }

    #[OA\Post(
        path: "/api/users",
        summary: "Create a new user",
        description: "Create a new user (Admin only)",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "email", "password", "role"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", example: "password123"),
                    new OA\Property(property: "password_confirmation", type: "string", example: "password123"),
                    new OA\Property(property: "role", type: "string", example: "user"),
                    new OA\Property(property: "receive_notifications", type: "boolean", example: true)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User created successfully"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            )
        ]
    )]
    public function store(Request $request)
    {
        if (!auth()->user()->can('create users')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to create users');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', 'string', Rule::in(['user', 'admin', 'manager', 'guest'])],
            'receive_notifications' => 'boolean'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'receive_notifications' => $request->receive_notifications ?? true,
        ]);

        // Assign role using Spatie
        $user->assignRole($request->role);

        return $this->successResponse($user->load('roles'), 'User created successfully', 201);
    }

    #[OA\Put(
        path: "/api/users/{id}",
        summary: "Update user profile",
        description: "Update user profile information",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", example: "john@example.com"),
                    new OA\Property(property: "role", type: "string", example: "user"),
                    new OA\Property(property: "receive_notifications", type: "boolean", example: true),
                    new OA\Property(property: "avatar", type: "string", example: "https://example.com/avatar.jpg")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User updated successfully"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            ),
            new OA\Response(
                response: 404,
                description: "User not found"
            ),
            new OA\Response(
                response: 422,
                description: "Validation error"
            )
        ]
    )]
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $targetUser = User::findOrFail($id);

        // Users can update their own profile, admins and super_admins can update any profile
        if ($user->id != $id && !in_array($user->role, ['admin', 'super_admin'])) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to update this user');
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($id)
            ],
            'role' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['user', 'admin', 'manager', 'guest', 'super_admin', 'review_manager', 'violation_manager', 'movie_manager'])
            ],
            'receive_notifications' => 'sometimes|boolean',
            'avatar' => 'sometimes|nullable|string|max:255'
        ]);

        // Only admins and super_admins can change roles
        if ($request->has('role') && !in_array($user->role, ['admin', 'super_admin'])) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to change user role');
        }

        // Convert role name to role_id
        $updateData = $request->only(['name', 'email', 'receive_notifications', 'avatar']);
        
        if ($request->has('role')) {
            $role = \Spatie\Permission\Models\Role::where('name', $request->role)->first();
            if ($role) {
                $updateData['role_id'] = $role->id;
            }
        }

        $targetUser->update($updateData);

        return $this->successResponse($targetUser, 'User updated successfully');
    }

    /**
     * Update user avatar
     */
    public function updateAvatar(Request $request)
    {
        $user = auth()->user();
        
        if (!$user) {
            return $this->errorResponse(ErrorCode::UNAUTHORIZED, null, 'User not authenticated');
        }

        $request->validate([
            'avatar' => 'required|string|max:255'
        ]);

        $user->update(['avatar' => $request->avatar]);

        return $this->successResponse($user, 'Avatar updated successfully');
    }

    #[OA\Delete(
        path: "/api/users/{id}",
        summary: "Delete user",
        description: "Delete a user (Admin only, cannot delete super_admin)",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User deleted successfully"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Cannot delete super_admin or insufficient permissions"
            ),
            new OA\Response(
                response: 404,
                description: "User not found"
            )
        ]
    )]
    public function destroy($id)
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to delete users');
        }

        $targetUser = User::findOrFail($id);

        // Cannot delete super_admin
        if ($targetUser->role === 'super_admin') {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Cannot delete super_admin user');
        }

        // Cannot delete yourself
        if (auth()->id() == $id) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Cannot delete your own account');
        }

        // Log the deletion$targetUser->delete();

        return $this->successResponse(null, 'User deleted successfully');
    }

    #[OA\Get(
        path: "/api/users/{id}/bookings",
        summary: "Get user bookings",
        description: "Get all bookings for a specific user",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User bookings retrieved successfully"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            ),
            new OA\Response(
                response: 404,
                description: "User not found"
            )
        ]
    )]
    public function getUserBookings($id)
    {
        $user = auth()->user();
        
        // Users can view their own bookings, admins can view any user's bookings
        if ($user->id != $id && !$user->can('view all bookings')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to view user bookings');
        }

        $targetUser = User::findOrFail($id);
        $bookings = $targetUser->bookings()->with([
            'showtime.movie', 
            'showtime.theater', 
            'seats',
            'snacks.snack'
        ])->paginate(15);

        return $this->successResponse($bookings, 'User bookings retrieved successfully');
    }

    #[OA\Get(
        path: "/api/users/{id}/reviews",
        summary: "Get user reviews",
        description: "Get all reviews for a specific user",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User reviews retrieved successfully"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            ),
            new OA\Response(
                response: 404,
                description: "User not found"
            )
        ]
    )]
    public function getUserReviews($id)
    {
        // For public API, skip auth check
        $targetUser = User::findOrFail($id);
        $reviews = $targetUser->reviews()->with('movie')->paginate(15);

        return $this->successResponse($reviews, 'User reviews retrieved successfully');
    }

    #[OA\Get(
        path: "/api/users/{id}/favorites",
        summary: "Get user favorites",
        description: "Get all favorite movies for a specific user",
        tags: ["User Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "User ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User favorites retrieved successfully"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            ),
            new OA\Response(
                response: 404,
                description: "User not found"
            )
        ]
    )]
    public function getUserFavorites($id)
    {
        $user = auth()->user();
        
        // Users can view their own favorites, admins can view any user's favorites
        if ($user->id != $id && !$user->can('view users')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to view user favorites');
        }

        $targetUser = User::findOrFail($id);
        $favorites = $targetUser->favorites()->paginate(15);

        return $this->successResponse($favorites, 'User favorites retrieved successfully');
    }

    /**
     * Assign role to user
     */
    public function assignRole(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $role = $request->input('role');
            
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role is required'
                ], 400);
            }

            // Validate role
            $validRoles = ['user', 'admin', 'manager', 'guest', 'super_admin', 'review_manager', 'violation_manager', 'movie_manager'];
            if (!in_array($role, $validRoles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid role'
                ], 400);
            }

            // Map role names to role IDs
            $roleMap = [
                'user' => 1,
                'admin' => 2,
                'movie_manager' => 3,
                'review_manager' => 4,
                'violation_manager' => 5
            ];
            
            $roleId = $roleMap[$role] ?? 1; // Default to user role
            
            // Update user role_id in database
            $user->update(['role_id' => $roleId]);
            
            // Assign role using Spatie
            $user->syncRoles([$role]);
            
            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $role
                ]
            ]);
        } catch (\Exception $e) {return response()->json([
                'success' => false,
                'message' => 'Error assigning role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Revoke admin role from user
     */
    public function revokeAdminRole(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Change role to user (role_id = 1)
            $user->update(['role_id' => 1]);
            $user->syncRoles(['user']);
            
            return response()->json([
                'success' => true,
                'message' => 'Admin role revoked successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => 'user'
                ]
            ]);
        } catch (\Exception $e) {return response()->json([
                'success' => false,
                'message' => 'Error revoking admin role: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle user status (activate/deactivate)
     */
    public function toggleStatus(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);
            $activate = $request->input('activate', true);
            
            // Update user status
            $user->update(['is_active' => $activate]);
            
            return response()->json([
                'success' => true,
                'message' => $activate ? 'User activated successfully' : 'User deactivated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $activate
                ]
            ]);
        } catch (\Exception $e) {return response()->json([
                'success' => false,
                'message' => 'Error toggling user status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export users to Excel
     */
    public function exportUsers()
    {
        try {
            // Get all users with their roles
            $users = User::with(['roles'])->orderBy('created_at', 'desc')->get();
            
            // Create CSV content
            $csvContent = "ID,Tên,Email,Vai trò,Trạng thái,Ngày tạo\n";
            
            foreach ($users as $user) {
                // Get primary role (first role or 'user' if no roles)
                $primaryRole = $user->roles->first()?->name ?? 'user';
                
                // Map role names to Vietnamese
                $roleMap = [
                    'admin' => 'Quản trị viên',
                    'super_admin' => 'Siêu quản trị viên',
                    'movie_manager' => 'Quản lý phim',
                    'review_manager' => 'Quản lý đánh giá',
                    'violation_manager' => 'Quản lý vi phạm',
                    'user' => 'Người dùng'
                ];
                
                $roleDisplay = $roleMap[$primaryRole] ?? $primaryRole;
                $statusDisplay = $user->is_active ? 'Hoạt động' : 'Không hoạt động';
                $createdAt = $user->created_at->format('d/m/Y H:i:s');
                
                $csvContent .= sprintf(
                    "%d,%s,%s,%s,%s,%s\n",
                    $user->id,
                    '"' . str_replace('"', '""', $user->name) . '"',
                    '"' . str_replace('"', '""', $user->email) . '"',
                    '"' . $roleDisplay . '"',
                    '"' . $statusDisplay . '"',
                    '"' . $createdAt . '"'
                );
            }
            
            // Add BOM for UTF-8
            $bom = "\xEF\xBB\xBF";
            $csvContent = $bom . $csvContent;
            
            return response($csvContent)
                ->header('Content-Type', 'text/csv; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="danh_sach_nguoi_dung_' . date('Y-m-d') . '.csv"');
                
        } catch (\Exception $e) {return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xuất dữ liệu: ' . $e->getMessage()
            ], 500);
        }
    }
}
