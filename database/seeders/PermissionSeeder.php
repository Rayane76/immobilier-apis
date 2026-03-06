<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
        Permission::create(['name' => 'ViewAny:User', 'guard_name' => 'web']);
        Permission::create(['name' => 'View:User', 'guard_name' => 'web']);
        Permission::create(['name' => 'Create:User', 'guard_name' => 'web']);
        Permission::create(['name' => 'Update:User', 'guard_name' => 'web']);
        Permission::create(['name' => 'Delete:User', 'guard_name' => 'web']);
        Permission::create(['name' => 'Restore:User', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDelete:User', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDeleteAny:User', 'guard_name' => 'web']);
        Permission::create(['name' => 'RestoreAny:User', 'guard_name' => 'web']);

        //create permissions for role model
        Permission::create(['name' => 'ViewAny:Role', 'guard_name' => 'web']);
        Permission::create(['name' => 'View:Role', 'guard_name' => 'web']);
        Permission::create(['name' => 'Create:Role', 'guard_name' => 'web']);
        Permission::create(['name' => 'Update:Role', 'guard_name' => 'web']);
        Permission::create(['name' => 'Delete:Role', 'guard_name' => 'web']);
        Permission::create(['name' => 'Restore:Role', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDelete:Role', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDeleteAny:Role', 'guard_name' => 'web']);
        Permission::create(['name' => 'RestoreAny:Role', 'guard_name' => 'web']);

        // create permissions for permission model
        Permission::create(['name' => 'ViewAny:Permission', 'guard_name' => 'web']);
        Permission::create(['name' => 'View:Permission', 'guard_name' => 'web']);
        Permission::create(['name' => 'Create:Permission', 'guard_name' => 'web']);
        Permission::create(['name' => 'Update:Permission', 'guard_name' => 'web']);
        Permission::create(['name' => 'Delete:Permission', 'guard_name' => 'web']);
        Permission::create(['name' => 'Restore:Permission', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDelete:Permission', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDeleteAny:Permission', 'guard_name' => 'web']);
        Permission::create(['name' => 'RestoreAny:Permission', 'guard_name' => 'web']);

        // create permissions for model_has_roles pivot (assigning roles to users)
        Permission::create(['name' => 'AssignRole:User', 'guard_name' => 'web']);
        Permission::create(['name' => 'RevokeRole:User', 'guard_name' => 'web']);

        // create permissions for role_has_permissions pivot (assigning permissions to roles)
        Permission::create(['name' => 'AssignPermission:Role', 'guard_name' => 'web']);
        Permission::create(['name' => 'RevokePermission:Role', 'guard_name' => 'web']);

        // create permissions for property model
        Permission::create(['name' => 'Create:Property',           'guard_name' => 'web']);
        Permission::create(['name' => 'Update:Property',           'guard_name' => 'web']);
        Permission::create(['name' => 'Delete:Property',           'guard_name' => 'web']);
        Permission::create(['name' => 'Restore:Property',          'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDelete:Property',      'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDeleteAny:Property',   'guard_name' => 'web']);
        Permission::create(['name' => 'RestoreAny:Property',       'guard_name' => 'web']);
        // ViewDeleted:Property  — view a single soft-deleted property (own record for agents)
        // ViewAnyDeleted:Property — browse the trashed listing (Super-Admin only in practice)
        Permission::create(['name' => 'ViewDeleted:Property',      'guard_name' => 'web']);
        Permission::create(['name' => 'ViewAnyDeleted:Property',   'guard_name' => 'web']);

        // create permissions for image model
        Permission::create(['name' => 'ViewAny:Image', 'guard_name' => 'web']);
        Permission::create(['name' => 'View:Image', 'guard_name' => 'web']);
        Permission::create(['name' => 'Create:Image', 'guard_name' => 'web']);
        Permission::create(['name' => 'Update:Image', 'guard_name' => 'web']);
        Permission::create(['name' => 'Delete:Image', 'guard_name' => 'web']);
        Permission::create(['name' => 'Restore:Image', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDelete:Image', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDeleteAny:Image', 'guard_name' => 'web']);
        Permission::create(['name' => 'RestoreAny:Image', 'guard_name' => 'web']);

        // create permissions for attribute model
        Permission::create(['name' => 'Create:Attribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'Update:Attribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'Delete:Attribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'Restore:Attribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDelete:Attribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDeleteAny:Attribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'RestoreAny:Attribute', 'guard_name' => 'web']);

        // create permissions for property type model
        Permission::create(['name' => 'Create:PropertyType', 'guard_name' => 'web']);
        Permission::create(['name' => 'Update:PropertyType', 'guard_name' => 'web']);
        Permission::create(['name' => 'Delete:PropertyType', 'guard_name' => 'web']);
        Permission::create(['name' => 'Restore:PropertyType', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDelete:PropertyType', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDeleteAny:PropertyType', 'guard_name' => 'web']);
        Permission::create(['name' => 'RestoreAny:PropertyType', 'guard_name' => 'web']);

        // create permissions for property type attribute pivot model (no soft deletes)
        Permission::create(['name' => 'ViewAny:PropertyTypeAttribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'View:PropertyTypeAttribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'Create:PropertyTypeAttribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'Update:PropertyTypeAttribute', 'guard_name' => 'web']);
        Permission::create(['name' => 'Delete:PropertyTypeAttribute', 'guard_name' => 'web']);

        // create permissions for region model
        Permission::create(['name' => 'Create:Region', 'guard_name' => 'web']);
        Permission::create(['name' => 'Update:Region', 'guard_name' => 'web']);
        Permission::create(['name' => 'Delete:Region', 'guard_name' => 'web']);
        Permission::create(['name' => 'Restore:Region', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDelete:Region', 'guard_name' => 'web']);
        Permission::create(['name' => 'ForceDeleteAny:Region', 'guard_name' => 'web']);
        Permission::create(['name' => 'RestoreAny:Region', 'guard_name' => 'web']);

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
            // Agents can view their own soft-deleted listings (e.g. to restore them)
            'ViewDeleted:Property',
            // Agents can browse their own deleted listings; the repository scopes
            // the query to created_by = user->id for non-Super-Admin callers.
            'ViewAnyDeleted:Property',

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
            'password' => Hash::make('admin-password'),
        ]);
        $user->assignRole($superAdmin);

        $user = \App\Models\User::factory()->create([
            'name' => 'Agent User',
            'email' => 'agent@example.com',
            'password' => Hash::make('agent-password'),
        ]);
        $user->assignRole($agent);

        $user = \App\Models\User::factory()->create([
            'name' => 'Visiteur User',
            'email' => 'visiteur@example.com',
            'password' => Hash::make('visiteur-password'),
        ]);
        $user->assignRole($visiteur);
    }
}
