<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Enums\ErrorCode;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Role & Permission Management")]
class RolePermissionController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: "/api/roles",
        summary: "Get all roles",
        description: "Get list of all roles with their permissions",
        tags: ["Role & Permission Management"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Roles retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Roles retrieved successfully"),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            )
        ]
    )]
    public function getRoles()
    {
        if (!auth()->user()->can('manage roles')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to manage roles');
        }

        $roles = Role::with('permissions')->get();
        return $this->successResponse($roles, 'Roles retrieved successfully');
    }

    #[OA\Get(
        path: "/api/permissions",
        summary: "Get all permissions",
        description: "Get list of all available permissions",
        tags: ["Role & Permission Management"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Permissions retrieved successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "message", type: "string", example: "Permissions retrieved successfully"),
                        new OA\Property(property: "data", type: "array", items: new OA\Items(type: "object"))
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            )
        ]
    )]
    public function getPermissions()
    {
        if (!auth()->user()->can('manage roles')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to manage permissions');
        }

        $permissions = Permission::all();
        return $this->successResponse($permissions, 'Permissions retrieved successfully');
    }

    #[OA\Post(
        path: "/api/roles",
        summary: "Create a new role",
        description: "Create a new role with specified permissions",
        tags: ["Role & Permission Management"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "permissions"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "editor"),
                    new OA\Property(property: "permissions", type: "array", items: new OA\Items(type: "string"), example: ["view movies", "edit movies"])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Role created successfully"
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
    public function createRole(Request $request)
    {
        if (!auth()->user()->can('manage roles')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to create roles');
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->givePermissionTo($request->permissions);

        return $this->successResponse($role->load('permissions'), 'Role created successfully', 201);
    }

    #[OA\Put(
        path: "/api/roles/{id}",
        summary: "Update a role",
        description: "Update role permissions",
        tags: ["Role & Permission Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Role ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "permissions", type: "array", items: new OA\Items(type: "string"), example: ["view movies", "edit movies"])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Role updated successfully"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Insufficient permissions"
            ),
            new OA\Response(
                response: 404,
                description: "Role not found"
            )
        ]
    )]
    public function updateRole(Request $request, $id)
    {
        if (!auth()->user()->can('manage roles')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to update roles');
        }

        $role = Role::findOrFail($id);
        
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,name'
        ]);

        $role->syncPermissions($request->permissions);

        return $this->successResponse($role->load('permissions'), 'Role updated successfully');
    }

    #[OA\Delete(
        path: "/api/roles/{id}",
        summary: "Delete a role",
        description: "Delete a role (cannot delete super_admin role)",
        tags: ["Role & Permission Management"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                description: "Role ID",
                required: true,
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Role deleted successfully"
            ),
            new OA\Response(
                response: 403,
                description: "Forbidden - Cannot delete super_admin role"
            ),
            new OA\Response(
                response: 404,
                description: "Role not found"
            )
        ]
    )]
    public function deleteRole($id)
    {
        if (!auth()->user()->can('manage roles')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to delete roles');
        }

        $role = Role::findOrFail($id);

        if ($role->name === 'super_admin') {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Cannot delete super_admin role');
        }

        $role->delete();

        return $this->successResponse(null, 'Role deleted successfully');
    }

    #[OA\Get(
        path: "/api/users/{id}/roles",
        summary: "Get user roles",
        description: "Get roles assigned to a specific user",
        tags: ["Role & Permission Management"],
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
                description: "User roles retrieved successfully"
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
    public function getUserRoles($id)
    {
        if (!auth()->user()->can('view users')) {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to view user roles');
        }

        $user = \App\Models\User::findOrFail($id);
        $roles = $user->roles;
        $permissions = $user->getAllPermissions();

        return $this->successResponse([
            'roles' => $roles,
            'permissions' => $permissions
        ], 'User roles and permissions retrieved successfully');
    }

    #[OA\Post(
        path: "/api/users/{id}/roles",
        summary: "Assign roles to user",
        description: "Assign roles to a specific user",
        tags: ["Role & Permission Management"],
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
                required: ["roles"],
                properties: [
                    new OA\Property(property: "roles", type: "array", items: new OA\Items(type: "string"), example: ["admin", "manager"])
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Roles assigned successfully"
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
    public function assignRoles(Request $request, $id)
    {
        // Debug: Log the request
        \Log::info('RolePermissionController::assignRoles called', [
            'user_id' => auth()->id(),
            'target_user_id' => $id,
            'request_data' => $request->all(),
            'user_role' => auth()->user()?->role,
            'user_permissions' => auth()->user()?->getAllPermissions()->pluck('name')->toArray()
        ]);

        // Simple permission check - allow super_admin and admin
        $currentUser = auth()->user();
        if ($currentUser->role !== 'super_admin' && $currentUser->role !== 'admin') {
            return $this->errorResponse(ErrorCode::FORBIDDEN, null, 'Insufficient permissions to assign roles');
        }

        $user = \App\Models\User::findOrFail($id);
        
        $request->validate([
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name'
        ]);

        $user->syncRoles($request->roles);

        // Also update the role_id field for backward compatibility
        if (!empty($request->roles)) {
            $primaryRole = Role::where('name', $request->roles[0])->first();
            if ($primaryRole) {
                $user->update(['role_id' => $primaryRole->id]);
            }
        }

        return $this->successResponse($user->load('roles'), 'Roles assigned successfully');
    }
}
