<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Criar roles
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $user = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        // Criar permissions
        $viewProfile = Permission::firstOrCreate(['name' => 'view profile', 'guard_name' => 'api']);
        $manageUsers = Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'api']);

        // Atribuir todas as permissions ao admin
        $admin->givePermissionTo(Permission::all());
    }
}