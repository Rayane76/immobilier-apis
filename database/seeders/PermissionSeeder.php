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

        // create permissions for attribute model
        Permission::create(['name' => 'Create:Attribute']);
        Permission::create(['name' => 'Update:Attribute']);
        Permission::create(['name' => 'Delete:Attribute']);
        Permission::create(['name' => 'Restore:Attribute']);
        Permission::create(['name' => 'ForceDelete:Attribute']);
        Permission::create(['name' => 'ForceDeleteAny:Attribute']);
        Permission::create(['name' => 'RestoreAny:Attribute']);

        // create permissions for property type model
        Permission::create(['name' => 'Create:PropertyType']);
        Permission::create(['name' => 'Update:PropertyType']);
        Permission::create(['name' => 'Delete:PropertyType']);
        Permission::create(['name' => 'Restore:PropertyType']);
        Permission::create(['name' => 'ForceDelete:PropertyType']);
        Permission::create(['name' => 'ForceDeleteAny:PropertyType']);
        Permission::create(['name' => 'RestoreAny:PropertyType']);

        // create permissions for property type attribute pivot model (no soft deletes)
        Permission::create(['name' => 'ViewAny:PropertyTypeAttribute']);
        Permission::create(['name' => 'View:PropertyTypeAttribute']);
        Permission::create(['name' => 'Create:PropertyTypeAttribute']);
        Permission::create(['name' => 'Update:PropertyTypeAttribute']);
        Permission::create(['name' => 'Delete:PropertyTypeAttribute']);

        // create permissions for region model
        Permission::create(['name' => 'Create:Region']);
        Permission::create(['name' => 'Update:Region']);
        Permission::create(['name' => 'Delete:Region']);
        Permission::create(['name' => 'Restore:Region']);
        Permission::create(['name' => 'ForceDelete:Region']);
        Permission::create(['name' => 'ForceDeleteAny:Region']);
        Permission::create(['name' => 'RestoreAny:Region']);

        // -------------------------------------------------------------------------
        // agent role
        // Can manage all business models.
        // On Property: can only act on records they created (enforced by policy).
        // ForceDeleteAny / RestoreAny are intentionally NOT granted — those are
        // reserved for Super-Admin who bypasses policies via Gate::before.
        // -------------------------------------------------------------------------
        $agent = Role::create(['name' => 'agent']);

        $agent->givePermissionTo([
            // Property (own records only — ownership enforced in PropertyPolicy)
            'Create:Property',
            'Update:Property',
            'Delete:Property',
            'Restore:Property',
            'ForceDelete:Property',

            // Image
            'ViewAny:Image',
            'View:Image',
            'Create:Image',
            'Update:Image',
            'Delete:Image',
            'Restore:Image',
            'ForceDelete:Image',

            // Attribute
            'Create:Attribute',
            'Update:Attribute',
            'Delete:Attribute',
            'Restore:Attribute',
            'ForceDelete:Attribute',

            // PropertyType
            'Create:PropertyType',
            'Update:PropertyType',
            'Delete:PropertyType',
            'Restore:PropertyType',
            'ForceDelete:PropertyType',

            // PropertyTypeAttribute (no soft deletes)
            'ViewAny:PropertyTypeAttribute',
            'View:PropertyTypeAttribute',
            'Create:PropertyTypeAttribute',
            'Update:PropertyTypeAttribute',
            'Delete:PropertyTypeAttribute',

            // Region
            'Create:Region',
            'Update:Region',
            'Delete:Region',
            'Restore:Region',
            'ForceDelete:Region',
        ]);

        // -------------------------------------------------------------------------
        // visiteur role — read-only access to published listings only
        // -------------------------------------------------------------------------
        $visiteur = Role::create(['name' => 'visiteur']);

        $superAdmin = Role::create(['name' => 'Super-Admin']);
        // gets all permissions via Gate::before rule; see AppServiceProvider

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
