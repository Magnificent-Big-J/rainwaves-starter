<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Rainwaves\LaraAuthSuite\TwoFactor\Contracts\ITwoFactorAuth;

class StarterUsersSeeder extends Seeder
{
    public function __construct(private ITwoFactorAuth $twoFactor)
    {
    }

    public function run(): void
    {
        $owner = $this->upsertUser('owner@rainwaves.test', 'Starter Owner', 'super-admin');
        $ops = $this->upsertUser('ops@rainwaves.test', 'Starter Ops', 'admin');
        $this->upsertUser('customer@rainwaves.test', 'Starter Customer', 'customer');

        if (! $owner->hasRole('super-admin')) {
            $owner->syncRoles(['super-admin']);
        }

        $this->twoFactor->enableEmailOtp($ops);
    }

    private function upsertUser(string $email, string $name, string $role): User
    {
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'email_verified_at' => Carbon::now(),
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }
}
