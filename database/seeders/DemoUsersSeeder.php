<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDemoUser('Admin User', 'admin@example.com', 'admin');
        $this->seedDemoUser('Editor User', 'editor@example.com', 'editor');
        $this->seedDemoUser('Viewer User', 'viewer@example.com', 'viewer');
    }

    private function seedDemoUser(string $name, string $email, string $role): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ],
        );

        $updates = [];

        if (blank($user->name)) {
            $updates['name'] = $name;
        }

        if (is_null($user->email_verified_at)) {
            $updates['email_verified_at'] = now();
        }

        if (! empty($updates)) {
            $user->update($updates);
        }

        $user->syncRoles([$role]);
    }
}
