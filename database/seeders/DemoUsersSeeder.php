<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDemoUser('Admin User', 'admin@example.com', 'admin', 'es', 'Europe/Madrid');
        $this->seedDemoUser('Editor User', 'editor@example.com', 'editor', 'es', 'Europe/Madrid');
        $this->seedDemoUser('Viewer User', 'viewer@example.com', 'viewer', 'es', 'Europe/Madrid');
    }

    private function seedDemoUser(string $name, string $email, string $role, string $preferredLocale, string $timezone): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
                'preferred_locale' => $preferredLocale,
                'timezone' => $timezone,
                'date_format' => 'locale_default',
                'time_format' => '24h',
            ],
        );

        $user->update([
            'preferred_locale' => $preferredLocale,
            'timezone' => $timezone,
            'date_format' => 'locale_default',
            'time_format' => '24h',
        ]);

        if (blank($user->name)) {
            $user->update(['name' => $name]);
        }

        if (is_null($user->email_verified_at)) {
            $user->update(['email_verified_at' => now()]);
        }

        $user->syncRoles([$role]);
    }
}
