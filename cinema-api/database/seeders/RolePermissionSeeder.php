<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Movie permissions
            'view movies',
            'create movies',
            'edit movies',
            'delete movies',
            'feature movies',
            'manage movie schedules',
            
            // Genre permissions
            'view genres',
            'create genres',
            'edit genres',
            'delete genres',
            
            // Theater permissions
            'view theaters',
            'create theaters',
            'edit theaters',
            'delete theaters',
            'manage theater operations',
            
            // Schedule permissions
            'view schedules',
            'create schedules',
            'edit schedules',
            'delete schedules',
            'manage showtimes',
            
            // Ticket permissions
            'view tickets',
            'create tickets',
            'edit tickets',
            'delete tickets',
            'view all tickets', // Admin only
            'manage ticket sales',
            'process refunds',
            
            // Review permissions
            'view reviews',
            'create reviews',
            'edit reviews',
            'delete reviews',
            'moderate reviews', // Admin only
            'approve reviews',
            
            // Comment permissions
            'view comments',
            'create comments',
            'edit comments',
            'delete comments',
            'moderate comments', // Admin only
            'approve comments',
            
            // User permissions
            'view users',
            'create users',
            'edit users',
            'delete users',
            'view user profiles',
            'manage user accounts',
            'manage roles', // Super Admin only
            
            // Financial permissions
            'view revenue reports',
            'manage pricing',
            'view financial statistics',
            'process payments',
            
            // System permissions
            'access admin panel',
            'view statistics',
            'manage settings',
            'view system logs',
            'manage backups',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission], ['name' => $permission, 'guard_name' => 'web']);
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'api'], ['name' => $permission, 'guard_name' => 'api']);
        }

        // Create roles and assign permissions
        $roles = [
            'super_admin' => $permissions, // All permissions
            
            'admin' => [
                // Movie management
                'view movies', 'create movies', 'edit movies', 'delete movies', 'feature movies', 'manage movie schedules',
                // Genre management
                'view genres', 'create genres', 'edit genres', 'delete genres',
                // Theater management
                'view theaters', 'create theaters', 'edit theaters', 'delete theaters', 'manage theater operations',
                // Schedule management
                'view schedules', 'create schedules', 'edit schedules', 'delete schedules', 'manage showtimes',
                // Ticket management
                'view all tickets', 'edit tickets', 'delete tickets', 'manage ticket sales', 'process refunds',
                // Review & Comment management
                'view reviews', 'moderate reviews', 'delete reviews', 'approve reviews',
                'view comments', 'moderate comments', 'delete comments', 'approve comments',
                // User management
                'view users', 'create users', 'edit users', 'delete users', 'view user profiles', 'manage user accounts',
                // Financial management
                'view revenue reports', 'manage pricing', 'view financial statistics', 'process payments',
                // System access
                'access admin panel', 'view statistics', 'manage settings', 'view system logs',
            ],
            
            'manager' => [
                // Movie operations
                'view movies', 'edit movies', 'feature movies', 'manage movie schedules',
                // Genre operations
                'view genres', 'edit genres',
                // Theater operations
                'view theaters', 'edit theaters', 'manage theater operations',
                // Schedule operations
                'view schedules', 'create schedules', 'edit schedules', 'manage showtimes',
                // Ticket operations
                'view all tickets', 'edit tickets', 'manage ticket sales', 'process refunds',
                // Review & Comment operations
                'view reviews', 'moderate reviews', 'approve reviews',
                'view comments', 'moderate comments', 'approve comments',
                // User operations
                'view users', 'view user profiles',
                // Financial operations
                'view revenue reports', 'view financial statistics',
                // System access
                'access admin panel', 'view statistics',
            ],
            
            'cashier' => [
                // Basic viewing
                'view movies', 'view genres', 'view theaters', 'view schedules',
                // Ticket operations
                'view tickets', 'create tickets', 'edit tickets', 'manage ticket sales', 'process refunds',
                // Review & Comment viewing
                'view reviews', 'view comments',
                // Limited user access
                'view users', 'view user profiles',
                // Basic financial
                'view revenue reports',
                // Limited system access
                'access admin panel',
            ],
            
            'user' => [
                // Viewing permissions
                'view movies', 'view genres', 'view theaters', 'view schedules',
                // Ticket operations
                'view tickets', 'create tickets', 'edit tickets',
                // Review & Comment operations
                'view reviews', 'create reviews', 'edit reviews',
                'view comments', 'create comments', 'edit comments',
            ],
            
            'guest' => [
                // Read-only access
                'view movies', 'view genres', 'view theaters', 'view schedules',
                'view reviews', 'view comments',
            ],
        ];

        foreach ($roles as $roleName => $rolePermissions) {
            $role = Role::firstOrCreate(['name' => $roleName], ['name' => $roleName, 'guard_name' => 'web']);
            // Ensure permissions exist for the same guard
            $permModels = Permission::whereIn('name', $rolePermissions)->where('guard_name', 'web')->get();
            $role->syncPermissions($permModels);
        }
    }
}
