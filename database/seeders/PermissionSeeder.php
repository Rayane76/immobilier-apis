<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // create permissions for user model
        Permission::create(['name' => 'ViewAny:User']);
        Permission::create(['name' => 'View:User']);
        Permission::create(['name' => 'Create:User']);
        Permission::create(['name' => 'Update:User']);
        Permission::create(['name' => 'Delete:User']);
        Permission::create(['name' => 'Restore:User']);
        Permission::create(['name' => 'ForceDelete:User']);
        Permission::create(['name' => 'ForceDeleteAny:User']);
        Permission::create(['name' => 'RestoreAny:User']);

        //create permissions for role model
        Permission::create(['name' => 'ViewAny:Role']);
        Permission::create(['name' => 'View:Role']);
        Permission::create(['name' => 'Create:Role']);
        Permission::create(['name' => 'Update:Role']);
        Permission::create(['name' => 'Delete:Role']);
        Permission::create(['name' => 'Restore:Role']);
        Permission::create(['name' => 'ForceDelete:Role']);
        Permission::create(['name' => 'ForceDeleteAny:Role']);
        Permission::create(['name' => 'RestoreAny:Role']);

        // create permissions for permission model
        Permission::create(['name' => 'ViewAny:Permission']);
        Permission::create(['name' => 'View:Permission']);
        Permission::create(['name' => 'Create:Permission']);
        Permission::create(['name' => 'Update:Permission']);
        Permission::create(['name' => 'Delete:Permission']);
        Permission::create(['name' => 'Restore:Permission']);
        Permission::create(['name' => 'ForceDelete:Permission']);
        Permission::create(['name' => 'ForceDeleteAny:Permission']);
        Permission::create(['name' => 'RestoreAny:Permission']);

        // create permissions for model_has_roles pivot (assigning roles to users)
        Permission::create(['name' => 'AssignRole:User']);
        Permission::create(['name' => 'RevokeRole:User']);

        // create permissions for role_has_permissions pivot (assigning permissions to roles)
        Permission::create(['name' => 'AssignPermission:Role']);
        Permission::create(['name' => 'RevokePermission:Role']);

        // create permissions for property model
        Permission::create(['name' => 'ViewAny:Property']);
        Permission::create(['name' => 'View:Property']);
        Permission::create(['name' => 'Create:Property']);
        Permission::create(['name' => 'Update:Property']);
        Permission::create(['name' => 'Delete:Property']);
        Permission::create(['name' => 'Restore:Property']);
        Permission::create(['name' => 'ForceDelete:Property']);
        Permission::create(['name' => 'ForceDeleteAny:Property']);
        Permission::create(['name' => 'RestoreAny:Property']);

        // create permissions for image model
        Permission::create(['name' => 'ViewAny:Image']);
        Permission::create(['name' => 'View:Image']);
        Permission::create(['name' => 'Create:Image']);
        Permission::create(['name' => 'Update:Image']);
        Permission::create(['name' => 'Delete:Image']);
        Permission::create(['name' => 'Restore:Image']);
        Permission::create(['name' => 'ForceDelete:Image']);
        Permission::create(['name' => 'ForceDeleteAny:Image']);
        Permission::create(['name' => 'RestoreAny:Image']);

        $agent = Role::create(['name' => 'agent']);
        $agent->givePermissionTo('ViewAny:Property');
        $agent->givePermissionTo('View:Property');
        $agent->givePermissionTo('Create:Property');
        $agent->givePermissionTo('Update:Property');
        $agent->givePermissionTo('Delete:Property');
        $agent->givePermissionTo('ViewAny:Image');
        $agent->givePermissionTo('View:Image');
        $agent->givePermissionTo('Create:Image');
        $agent->givePermissionTo('Update:Image');
        $agent->givePermissionTo('Delete:Image');

        $visiteur = Role::create(['name' => 'visiteur']);
        $visiteur->givePermissionTo('ViewAny:Property');
        $visiteur->givePermissionTo('View:Property');

        $superAdmin = Role::create(['name' => 'Super-Admin']);
        // gets all permissions via Gate::before rule; see AuthServiceProvider

        // create demo users
        $user = \App\Models\User::factory()->create([
            'name' => 'Super-Admin User',
            'email' => 'super-admin@example.com',
        ]);
        $user->assignRole($superAdmin);

        $user = \App\Models\User::factory()->create([
            'name' => 'Agent User',
            'email' => 'agent@example.com',
        ]);
        $user->assignRole($agent);

        $user = \App\Models\User::factory()->create([
            'name' => 'Visiteur User',
            'email' => 'visiteur@example.com',
        ]);
        $user->assignRole($visiteur);
    }
}
