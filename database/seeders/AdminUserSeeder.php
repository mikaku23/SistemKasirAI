<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::withTrashed()
            ->where('slug', 'admin')
            ->orWhere('name', 'Admin')
            ->first();

        if (! $adminRole) {
            $adminRole = Role::create([
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'Role administrator dengan akses penuh ke dashboard.',
                'is_active' => true,
            ]);
        } else {
            if ($adminRole->trashed()) {
                $adminRole->restore();
            }

            $adminRole->forceFill([
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => $adminRole->description ?: 'Role administrator dengan akses penuh ke dashboard.',
                'is_active' => true,
            ])->save();
        }

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'role_id' => $adminRole->id,
                'location_id' => null,
                'name' => 'Administrator',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'no_hp' => '081234567890',
                'password' => Hash::make('123'),
                'security_question' => 'Apa nama sekolah pertama?',
                'security_answer' => 'admin',
                'avatar' => null,
                'is_active' => true,
                'last_login_at' => null,
                'last_password_changed_at' => null,
                'remember_token' => Str::random(10),
            ]
        );
    }
}
