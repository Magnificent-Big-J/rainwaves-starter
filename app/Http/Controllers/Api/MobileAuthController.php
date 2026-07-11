<?php

namespace App\Http\Controllers\Api;

use App\Contracts\MobileAuthServiceInterface;
use App\DTO\MobileLoginResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\LoginRequest;
use App\Http\Requests\Mobile\TwoFactorChallengeRequest;
use App\Http\Resources\AuthUserResource;
use App\Http\Resources\DeviceResource;
use App\Http\Responses\Envelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Rainwaves\LaraAuthSuite\Support\Enums\TokenType;

class MobileAuthController extends Controller
{
    public function __construct(
        private readonly MobileAuthServiceInterface $service
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->service->login(
            $request->string('email')->toString(),
            $request->string('password')->toString(),
            $request->validated('device'),
        );

        if ($result->requiresTwoFactor) {
            return Envelope::success([
                'two_factor_required' => true,
                'pending_auth_id' => $result->pendingAuthId,
                'channel' => $result->channel?->value,
            ], 'Two-factor verification required.');
        }

        return $this->loginResponse($result, 'Logged in.');
    }

    public function twoFactor(TwoFactorChallengeRequest $request): JsonResponse
    {
        $result = $this->service->completeTwoFactor(
            $request->string('pending_auth_id')->toString(),
            $request->string('code')->toString(),
            $request->filled('channel') ? $request->string('channel')->toString() : null,
        );

        return $this->loginResponse($result, 'Logged in.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->service->logout($request->user());

        return Envelope::success(null, 'Logged out.');
    }

    private function loginResponse(MobileLoginResult $result, string $message): JsonResponse
    {
        return Envelope::success([
            'token' => $result->token,
            'token_type' => TokenType::Bearer->value,
            'user' => AuthUserResource::make($result->user)->resolve(request()),
            'device' => DeviceResource::make($result->device)
                ->currentTokenId($result->device->personal_access_token_id)
                ->resolve(request()),
        ], $message);
    }
}
