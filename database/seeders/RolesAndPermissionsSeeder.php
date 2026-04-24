<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private array $permissions = [
        'dashboard.view',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'roles.view',
        'roles.manage',
        'permissions.view',
        'permissions.manage',
        'profile.view',
        'profile.update',
        'profile.manage_avatar',
        'media.view',
        'media.upload',
        'media.delete',
        'payments.view',
        'payments.create',
        'payments.manage',
        'settings.view',
        'settings.manage',
        'activity.view',
        'horizon.view',
    ];

    /**
     * @var array<string, list<string>>
     */
    private array $rolePermissions = [
        'super-admin' => [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.manage',
            'permissions.view',
            'permissions.manage',
            'profile.view',
            'profile.update',
            'profile.manage_avatar',
            'media.view',
            'media.upload',
            'media.delete',
            'payments.view',
            'payments.create',
            'payments.manage',
            'settings.view',
            'settings.manage',
            'activity.view',
            'horizon.view',
        ],
        'admin' => [
            'dashboard.view',
            'users.view',
            'users.create',
            'users.update',
            'roles.view',
            'permissions.view',
            'profile.view',
            'profile.update',
            'profile.manage_avatar',
            'media.view',
            'media.upload',
            'payments.view',
            'payments.create',
            'payments.manage',
            'settings.view',
            'activity.view',
            'horizon.view',
        ],
        'staff' => [
            'dashboard.view',
            'profile.view',
            'profile.update',
            'profile.manage_avatar',
            'media.view',
            'media.upload',
            'payments.view',
            'payments.create',
        ],
        'customer' => [
            'dashboard.view',
            'profile.view',
            'profile.update',
            'profile.manage_avatar',
            'payments.view',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        foreach ($this->rolePermissions as $roleName => $permissions) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
