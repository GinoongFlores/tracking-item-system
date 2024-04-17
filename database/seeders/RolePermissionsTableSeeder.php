<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class RolePermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        // clear the pivot table
        // DB::table('permission_roles')->truncate();

        // super admin
        $superAdminRole = Role::where('role_name', 'super_admin')->first();
        $superAdminPermission = Permission::whereIn('permission_name', [
            'view_pending_items',
            'view_approved_items',

            'activate_users',
            'deactivate_users',
            'add_users',
            'edit_users',
            'view_users',
            'delete_users',

            'add_company',
            'edit_company',
            'view_company',
            'delete_company',
            'restore_company',

            'deactivate_admin',
            'activate_admin',
            'add_admin',
            'view_admin',
            'edit_admin',
            'delete_admin',

            'view_transfer_item',
            'delete_transfer_item',
            'restore_transfer_item',
        ])->pluck('id'); // get the ids of the permissions to avoid duplicates

         // attach the permission to super admin role without creating duplicates
        $superAdminRole->permissions()->syncWithoutDetaching($superAdminPermission);

        //  admin
        $adminRole = Role::where('role_name', 'admin')->first();
        $adminRolePermission = Permission::whereIn('permission_name', [
            // super_admin & admin per company
            'activate_users',
            'deactivate_users',

            'add_users',
            'edit_users',
            'view_users',
            'delete_users',

            'add_item',
            'edit_item',
            'view_item',
            'delete_item',
            'restore_item',

            'view_transfer_item',
            'delete_transfer_item',
            'restore_transfer_item',
        ])->pluck('id');

        // attach the permission to admin role
        $adminRole->permissions()->syncWithoutDetaching($adminRolePermission);

        // users
        $userRole = Role::where('role_name', 'user')->first();
        $userRolePermission = Permission::whereIn('permission_name', [
            'add_item',
            'edit_item',
            'view_item',
            'delete_item',
            'restore_item',

            'transfer_item',
            'view_transfer_item',
            'delete_transfer_item',
            'restore_transfer_item',
        ])->pluck('id');

        // attach the permission to user role
        $userRole->permissions()->syncWithoutDetaching($userRolePermission);
    }
}
