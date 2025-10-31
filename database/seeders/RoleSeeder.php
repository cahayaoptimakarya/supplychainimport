<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full access to system'],
            ['name' => 'User', 'slug' => 'user', 'description' => 'Standard user role'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'description' => $role['description'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // Seed basic menu structure
        $menuRows = [
            ['name' => 'Dashboard', 'slug' => 'dashboard', 'route' => 'dashboard', 'icon' => 'home', 'parent_slug' => null, 'sort_order' => 0],
            ['name' => 'Master Data', 'slug' => 'master-data', 'route' => null, 'icon' => 'database', 'parent_slug' => null, 'sort_order' => 10],
            // Grouped: Items page contains Item Categories as a tab
            ['name' => 'Items', 'slug' => 'items', 'route' => 'admin.masterdata.items.index', 'icon' => 'box', 'parent_slug' => 'master-data', 'sort_order' => 11],
            ['name' => 'UOM', 'slug' => 'uoms', 'route' => 'admin.masterdata.uom.index', 'icon' => 'scale', 'parent_slug' => 'master-data', 'sort_order' => 12],
            // Grouped: Suppliers page contains Supplier Categories as a tab
            ['name' => 'Suppliers', 'slug' => 'suppliers', 'route' => 'admin.masterdata.suppliers.index', 'icon' => 'box', 'parent_slug' => 'master-data', 'sort_order' => 13],
            ['name' => 'Warehouses', 'slug' => 'warehouses', 'route' => 'admin.masterdata.warehouses.index', 'icon' => 'home', 'parent_slug' => 'master-data', 'sort_order' => 14],
            ['name' => 'Users', 'slug' => 'users', 'route' => 'admin.masterdata.users.index', 'icon' => 'users', 'parent_slug' => 'master-data', 'sort_order' => 20],
            ['name' => 'Roles', 'slug' => 'roles', 'route' => 'admin.masterdata.roles.index', 'icon' => 'shield', 'parent_slug' => 'master-data', 'sort_order' => 21],
            ['name' => 'Menus', 'slug' => 'menus', 'route' => 'admin.masterdata.menus.index', 'icon' => 'menu', 'parent_slug' => 'master-data', 'sort_order' => 22],
            ['name' => 'Permissions', 'slug' => 'permissions', 'route' => 'admin.masterdata.permissions.index', 'icon' => 'lock', 'parent_slug' => 'master-data', 'sort_order' => 23],
        ];

        // Insert parents first
        foreach ($menuRows as $menu) {
            if ($menu['parent_slug'] === null) {
                DB::table('menus')->updateOrInsert(
                    ['slug' => $menu['slug']],
                    [
                        'name' => $menu['name'],
                        'route' => $menu['route'],
                        'icon' => $menu['icon'],
                        'parent_id' => null,
                        'sort_order' => $menu['sort_order'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        // Then children
        foreach ($menuRows as $menu) {
            if ($menu['parent_slug'] !== null) {
                $parent = DB::table('menus')->where('slug', $menu['parent_slug'])->first();
                DB::table('menus')->updateOrInsert(
                    ['slug' => $menu['slug']],
                    [
                        'name' => $menu['name'],
                        'route' => $menu['route'],
                        'icon' => $menu['icon'],
                        'parent_id' => $parent?->id,
                        'sort_order' => $menu['sort_order'],
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        // Deactivate deprecated child menus that are now grouped into tabs
        DB::table('menus')
            ->whereIn('slug', ['categories', 'supplier-categories'])
            ->update(['is_active' => false, 'updated_at' => now()]);

        // Grant Admin full permissions to all menus
        $adminRole = DB::table('roles')->where('slug', 'admin')->first();
        if ($adminRole) {
            $menus = DB::table('menus')->get();
            foreach ($menus as $m) {
                DB::table('permission_menu')->updateOrInsert(
                    ['role_id' => $adminRole->id, 'menu_id' => $m->id],
                    [
                        'can_view' => true,
                        'can_create' => true,
                        'can_update' => true,
                        'can_delete' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
