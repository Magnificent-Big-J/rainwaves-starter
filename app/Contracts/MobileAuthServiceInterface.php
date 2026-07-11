<?php

namespace App\Contracts;

use App\DTO\MobileLoginResult;
use App\Models\User;
use Illuminate\Validation\ValidationException;

interface MobileAuthServiceInterface
{
    /**
     * Attempt a mobile credential login. Returns a token immediately or a
     * pending two-factor challenge to be completed via completeTwoFactor().
     *
     * @param  array  $device  Validated device payload (uuid, platform, ...)
     *
     * @throws ValidationException on bad credentials
     */
    public function login(string $email, string $password, array $device): MobileLoginResult;

    /**
     * Complete a pending two-factor login challenge and issue the device token.
     *
     * @throws ValidationException on invalid/expired challenge or code
     */
    public function completeTwoFactor(string $pendingAuthId, string $code, ?string $channel = null): MobileLoginResult;

    /** Revoke the current access token and detach it from its device. */
    public function logout(User $user): void;
}
