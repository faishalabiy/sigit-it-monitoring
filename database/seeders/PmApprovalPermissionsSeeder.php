<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class PmApprovalPermissionsSeeder extends Seeder {
    public function run():void {
        $role=Role::where('slug','super-admin')->first();
        if (!$role) return;
        $role->permissions=array_values(array_unique(array_merge(
            $role->permissions ?? [],['pm.approve']
        )));
        $role->save();
    }
}