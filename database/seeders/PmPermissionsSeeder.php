<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class PmPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Preserve custom permissions: only append PM permissions to existing super-admin.
        $admin = Role::where('slug','super-admin')->first();
        if (! $admin) return;

        $admin->permissions = array_values(array_unique(array_merge(
            $admin->permissions ?? [], ['pm.view','pm.manage']
        )));
        $admin->save();
    }
}