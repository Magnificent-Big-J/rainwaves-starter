<?php

namespace App\DTO;

use App\Models\Device;
use App\Models\User;
use Rainwaves\LaraAuthSuite\Support\Enums\TwoFactorChannel;

readonly class MobileLoginResult
{
    public function __construct(
        public User $user,
        public ?string $token = null,
        public ?Device $device = null,
        public bool $requiresTwoFactor = false,
        public ?TwoFactorChannel $channel = null,
        public ?string $pendingAuthId = null,
    ) {}
}
