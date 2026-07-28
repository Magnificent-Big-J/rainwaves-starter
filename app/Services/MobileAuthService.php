<?php

namespace App\Services;

use App\Contracts\MobileAuthServiceInterface;
use App\DTO\MobileLoginResult;
use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Rainwaves\LaraAuthSuite\Contracts\AuthService;
use Rainwaves\LaraAuthSuite\Contracts\ITwoFactorRequirement;
use Rainwaves\LaraAuthSuite\Domain\Events\UserLoggedIn;
use Rainwaves\LaraAuthSuite\Support\AuthxConfig;
use Rainwaves\LaraAuthSuite\Support\Enums\PendingAuthPurpose;
use Rainwaves\LaraAuthSuite\Support\Enums\TwoFactorChannel;
use Rainwaves\LaraAuthSuite\Support\PendingAuthManager;
use Rainwaves\LaraAuthSuite\TwoFactor\Contracts\ITwoFactorAuth;

readonly class MobileAuthService implements MobileAuthServiceInterface
{
    public const TOKEN_ABILITY = 'mobile';

    public const CHANNEL_RECOVERY = 'recovery';

    private const PENDING_DEVICE_PREFIX = 'mobile:pending-device:';

    public function __construct(
        private AuthService $auth,
        private ITwoFactorRequirement $twoFactorRequirement,
        private ITwoFactorAuth $twoFactor,
        private PendingAuthManager $pendingAuth,
    ) {}

    public function login(string $email, string $password, array $device): MobileLoginResult
    {
        $user = $this->auth->attemptLogin($email, $password);

        $decision = $this->twoFactorRequirement->decide($user);

        if ($decision->requiresChallenge()) {
            $channel = $this->twoFactor->currentChannel($user) ?? AuthxConfig::defaultTwoFactorChannel();
            $pendingAuthId = $this->pendingAuth->startToken($user, PendingAuthPurpose::LoginChallenge);

            // Stash the device payload so the client doesn't resend it on verify.
            Cache::put(
                self::PENDING_DEVICE_PREFIX.$pendingAuthId,
                $device,
                max(60, (int) config('authx.2fa.otp.expiry_seconds', 180))
            );

            if ($channel === TwoFactorChannel::Email) {
                $this->twoFactor->sendEmailOtp($user);
            }

            return new MobileLoginResult(
                user: $user,
                requiresTwoFactor: true,
                channel: $channel,
                pendingAuthId: $pendingAuthId,
            );
        }

        if ($decision->requiresSetup()) {
            $pendingAuthId = $this->pendingAuth->startToken($user, PendingAuthPurpose::MfaSetup);

            // Stash the device payload so the client doesn't resend it once setup completes.
            Cache::put(
                self::PENDING_DEVICE_PREFIX.$pendingAuthId,
                $device,
                max(60, (int) config('authx.2fa.otp.expiry_seconds', 180))
            );

            return new MobileLoginResult(
                user: $user,
                requiresSetup: true,
                pendingAuthId: $pendingAuthId,
                allowedChannels: AuthxConfig::twoFactorChannels(),
            );
        }

        return $this->issueDeviceToken($user, $device);
    }

    public function completeTwoFactor(string $pendingAuthId, string $code, ?string $channel = null): MobileLoginResult
    {
        $user = $this->pendingAuth->getTokenUser($pendingAuthId);

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'pending_auth_id' => ['This login challenge is invalid or has expired. Please log in again.'],
            ]);
        }

        if (! $this->verifyCode($user, $code, $channel)) {
            throw ValidationException::withMessages([
                'code' => ['Invalid or expired verification code.'],
            ]);
        }

        $device = Cache::pull(self::PENDING_DEVICE_PREFIX.$pendingAuthId);
        $this->pendingAuth->clearToken($pendingAuthId);

        if (! is_array($device)) {
            throw ValidationException::withMessages([
                'pending_auth_id' => ['This login challenge is invalid or has expired. Please log in again.'],
            ]);
        }

        return $this->issueDeviceToken($user, $device);
    }

    public function logout(User $user): void
    {
        $token = $user->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            Device::where('personal_access_token_id', $token->getKey())
                ->update(['personal_access_token_id' => null]);

            $token->delete();
        }

        $this->twoFactor->clearVerified($user);
    }

    /**
     * Upsert the device, replace any token it previously held, and issue a
     * fresh PAT named by the device uuid with the mobile ability.
     */
    private function issueDeviceToken(User $user, array $devicePayload): MobileLoginResult
    {
        [$plainTextToken, $device] = DB::transaction(function () use ($user, $devicePayload) {
            $device = $user->devices()->updateOrCreate(
                ['uuid' => $devicePayload['uuid']],
                collect($devicePayload)->except('uuid')->put('last_seen_at', now())->all()
            );

            // One live token per device: drop the previous one before issuing.
            if ($device->personal_access_token_id !== null) {
                PersonalAccessToken::whereKey($device->personal_access_token_id)->delete();
            }

            $newToken = $user->createToken($device->uuid, [self::TOKEN_ABILITY]);

            $device->forceFill(['personal_access_token_id' => $newToken->accessToken->getKey()])->save();

            return [$newToken->plainTextToken, $device];
        });

        $this->twoFactor->markVerifiedTokenId($device->personal_access_token_id);

        event(new UserLoggedIn($user));

        return new MobileLoginResult(user: $user, token: $plainTextToken, device: $device);
    }

    private function verifyCode(User $user, string $code, ?string $channel): bool
    {
        $channel ??= $this->twoFactor->currentChannel($user)?->value;

        return match ($channel) {
            TwoFactorChannel::Email->value => $this->twoFactor->verifyOtp($user, $code),
            TwoFactorChannel::Totp->value => $this->twoFactor->verifyAuthenticatorApp($user, $code),
            self::CHANNEL_RECOVERY => $this->twoFactor->verifyRecoveryCode($user, $code),
            default => false,
        };
    }
}
