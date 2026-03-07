<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // user & role management
            'users-management',
            'roles-management',

            // operations
            'tables-management',
            'areas-management',
            'events-management',

            // transaction
            'pos-access',
            'bookings-management',
            'transaction-history-view',
            'transaction-checker-access',

            // production
            'inventory-management',
            'bom-management',
            'kitchen-access',
            'bar-access',
            'stock-opname-management',

            // customer
            'customers-management',
            'customer-keep-management',
            'rewards-management',

            // entertainment
            'song-requests-management',
            'display-messages-management',

            // system
            'waiter-performance-view',
            'settings-management',
            'accurate-sync',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles
        $roles = [
            'Administrator',
            'Manager',
            'Cashier',
            'Waiter/Server',
            'DJ',
            'Kitchen',
            'Bar',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'Administrator']);
        $adminRole->givePermissionTo(Permission::all());
    }
}
