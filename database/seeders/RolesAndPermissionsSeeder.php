<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Reset cached roles and permissions
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // Dashboard Permissions
    Permission::create(['name' => 'view dashboard']);

    // Manage Users Permissions
    Permission::create(['name' => 'create users']);
    Permission::create(['name' => 'edit users']);
    Permission::create(['name' => 'delete users']);
    Permission::create(['name' => 'view users']);

    // Group Permissions
    Permission::create(['name' => 'create groups']);
    Permission::create(['name' => 'edit groups']);
    Permission::create(['name' => 'delete groups']);
    Permission::create(['name' => 'view groups']);

    // Contribution Permissions
    Permission::create(['name' => 'make contributions']);
    Permission::create(['name' => 'view contributions']);
    Permission::create(['name' => 'edit contributions']);

    // Group Members Permissions
    Permission::create(['name' => 'add members']);
    Permission::create(['name' => 'remove members']);
    Permission::create(['name' => 'ban members']);
    Permission::create(['name' => 'edit members']);
    Permission::create(['name' => 'view members']);

    // Loan Permissions
    Permission::create(['name' => 'request loans']);
    Permission::create(['name' => 'approve loans']);
    Permission::create(['name' => 'view loans']);


    // Roles
    $superAdmin = Role::create(['name' => 'super-admin']);
    $participant = Role::firstOrCreate(['name' => 'participant']);
    $groupAdmin = Role::create(['name' => 'group-admin']);
    $groupMember = Role::create(['name' => 'group-member']);

    // Assign permissions to roles
    $superAdmin->givePermissionTo(Permission::all());

    $groupAdmin->givePermissionTo([
      'view dashboard', 'create groups', 'edit groups', 'delete groups', 'freeze contributions',
      'view groups', 'make contributions', 'view contributions', 'edit contributions', 'edit members',
      'request loans', 'approve loans', 'view loans', 'add members', 'remove members', 'ban members'
    ]);

    $groupMember->givePermissionTo([
      'view groups', 'make contributions', 'view contributions',
      'request loans', 'view loans', 'edit members', 'view members'
    ]);
  }
}
