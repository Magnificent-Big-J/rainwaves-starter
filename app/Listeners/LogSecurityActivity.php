<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Rainwaves\LaraAuthSuite\Domain\Events\AuthenticationRateLimited;
use Rainwaves\LaraAuthSuite\Domain\Events\AuthenticationStateRevoked;
use Rainwaves\LaraAuthSuite\Domain\Events\CredentialsChanged;
use Rainwaves\LaraAuthSuite\Domain\Events\LoginFailed;
use Rainwaves\LaraAuthSuite\Domain\Events\PasswordResetCompleted;
use Rainwaves\LaraAuthSuite\Domain\Events\RecoveryCodesRegenerated;
use Rainwaves\LaraAuthSuite\Domain\Events\RecoveryCodeUsed;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorChallenged;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorDisabled;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorEnabled;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorSetupRequired;
use Rainwaves\LaraAuthSuite\Domain\Events\TwoFactorVerified;
use Rainwaves\LaraAuthSuite\Domain\Events\UserLoggedIn;
use Rainwaves\LaraAuthSuite\Domain\Events\UserRegistered;

class LogSecurityActivity
{
    // These get a warning-level log line and a 'severity: high' property on
    // top of the normal activity-log entry — the ones an admin actually
    // wants to notice, not just be able to look up after the fact.
    private const HIGH_SEVERITY = [
        AuthenticationRateLimited::class,
        AuthenticationStateRevoked::class,
        TwoFactorDisabled::class,
        RecoveryCodeUsed::class,
    ];

    public function handle(object $event): void
    {
        [$slug, $description, $subject, $properties] = match (true) {
            $event instanceof UserLoggedIn => ['login', 'User logged in', $event->user, []],
            $event instanceof UserRegistered => ['registered', 'User registered', $event->user, []],
            $event instanceof PasswordResetCompleted => ['password_reset', 'Password reset completed', $event->user, []],
            $event instanceof TwoFactorChallenged => ['2fa_challenged', 'Two-factor challenge issued', $event->user, ['channel' => $event->channel]],
            $event instanceof TwoFactorVerified => ['2fa_verified', 'Two-factor challenge verified', $event->user, ['channel' => $event->channel]],
            $event instanceof CredentialsChanged => ['credentials_changed', 'Credentials changed', $event->user, []],
            $event instanceof AuthenticationStateRevoked => ['auth_revoked', 'Standing sessions and tokens revoked', $event->user, []],
            $event instanceof TwoFactorEnabled => ['2fa_enabled', 'Two-factor authentication enabled', $event->user, ['channel' => $event->channel->value]],
            $event instanceof TwoFactorDisabled => ['2fa_disabled', 'Two-factor authentication disabled', $event->user, []],
            $event instanceof RecoveryCodesRegenerated => ['recovery_codes_regenerated', 'Recovery codes regenerated', $event->user, []],
            $event instanceof TwoFactorSetupRequired => ['2fa_setup_required', 'Two-factor setup required at login', $event->user, []],
            $event instanceof RecoveryCodeUsed => ['recovery_code_used', 'Recovery code consumed', $event->user, []],
            // No subject/causer for these two — the package deliberately
            // keeps login-failure and rate-limit events non-enumerating
            // (no confirmed account binding), and looking one up by email
            // here would quietly undo that protection.
            $event instanceof LoginFailed => ['login_failed', 'Login attempt failed', null, ['email' => $event->email]],
            $event instanceof AuthenticationRateLimited => ['rate_limited', 'Authentication rate limit triggered', null, [
                'policy' => $event->policy,
                'dimension' => $event->dimension,
                'account' => $event->accountIdentifier,
                'ip' => $event->ip,
                'retry_after_seconds' => $event->retryAfterSeconds,
            ]],
            default => [null, null, null, []],
        };

        if ($slug === null || ! function_exists('activity')) {
            return;
        }

        $severity = in_array($event::class, self::HIGH_SEVERITY, true) ? 'high' : 'normal';
        $properties['severity'] = $severity;

        $request = request();
        $properties['ip'] ??= $request->ip();
        $userAgent = $request->userAgent();
        if ($userAgent) {
            $properties['user_agent'] = substr($userAgent, 0, 255);
        }

        $log = activity('security')->withProperties($properties)->event($slug);

        if ($subject) {
            $log = $log->performedOn($subject)->causedBy($subject);
        }

        $log->log($description);

        if ($severity === 'high') {
            Log::warning("[security] {$description}", array_merge(['event' => $event::class], $properties));
        }
    }
}
