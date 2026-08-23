<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Idempotent (firstOrCreate). En scope central : rôles RBAC + Ghost Admin
     * (table `admin_users` absente du schéma tenant). En contexte tenancy
     * (tenants:seed) : Ghost User (table `users` absente du schéma central).
     */
    public function run(): void
    {
        if (! tenancy()->initialized) {
            $this->call(AdminRolesSeeder::class);
            $this->call(PlansSeeder::class);

            $role = Role::where('name', AdminRolesSeeder::ROLE_SUPER_ADMIN)
                ->where('guard_name', 'admin')
                ->firstOrFail();

            $admin = AdminUser::firstOrCreate(
                ['email' => 'admin@cakeman.ci'],
                ['name' => 'Ghost Admin', 'password' => Hash::make('R00t7414')],
            );

            $admin->roles()->syncWithoutDetaching($role->id);
        } else {
            User::firstOrCreate(
                ['email' => 'tenant@cakeman.ci'],
                ['name' => 'Ghost User', 'password' => Hash::make('R00t7414')],
            );
        }
    }
}
