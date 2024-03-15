<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class RolePermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('role_name', 'super_admin')->first();
        $superAdminPermission = Permission::whereIn('permission_name', [
            'view_pending_items',
            'view_approved_items',

            'add_company',
            'edit_company',
            'view_company',
            'delete_company',

            'add_admin',
            'view_admin',
            'edit_admin',
            'delete_admin',

            'deactivate_admin',
            'deactivate_user'
        ])->pluck('id'); // get the ids of the permissions to avoid duplicates

         // attach the permission to super admin role
        $superAdminRole->permissions()->attach($superAdminPermission);

        //  admin
        $adminRole = Role::where('role_name', 'admin')->first();
        $adminRolePermission = Permission::whereIn('permission_name', [
            // super_admin & admin per company
            'add_users',
            'edit_users',
            'view_users',
            'delete_users',
        ])->pluck('id');

        // attach the permission to admin role
        $adminRole->permissions()->attach($adminRolePermission);
    }
}
