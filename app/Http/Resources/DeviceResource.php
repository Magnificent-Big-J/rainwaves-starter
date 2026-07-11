<?php

namespace App\Http\Resources;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Laravel\Sanctum\PersonalAccessToken;

/** @mixin Device */
class DeviceResource extends JsonResource
{
    private ?int $currentTokenId = null;

    /** Mark which token is "current" when the request itself is not token-authenticated (login response). */
    public function currentTokenId(?int $tokenId): static
    {
        $this->currentTokenId = $tokenId;

        return $this;
    }

    public function toArray(Request $request): array
    {
        $currentToken = $request->user()?->currentAccessToken();

        return [
            'uuid' => $this->uuid,
            'platform' => $this->platform->value,
            'platform_label' => $this->platform->label(),
            'model' => $this->model,
            'os_version' => $this->os_version,
            'app_version' => $this->app_version,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'is_current' => $this->personal_access_token_id !== null
                && $this->personal_access_token_id === ($this->currentTokenId
                    ?? ($currentToken instanceof PersonalAccessToken ? $currentToken->getKey() : null)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
