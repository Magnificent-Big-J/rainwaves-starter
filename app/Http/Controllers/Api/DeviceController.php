<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mobile\RegisterDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Http\Responses\Envelope;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $devices = $request->user()->devices()->latest('last_seen_at')->get();

        return Envelope::success(DeviceResource::collection($devices));
    }

    /**
     * Upsert the calling user's device by uuid and link the current access
     * token to it (heartbeat: app start / push-token or version change).
     */
    public function store(RegisterDeviceRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $user = $request->user();
        $currentToken = $user->currentAccessToken();

        $device = $user->devices()->updateOrCreate(
            ['uuid' => $payload['uuid']],
            collect($payload)->except('uuid')->put('last_seen_at', now())->all()
        );

        if ($currentToken instanceof PersonalAccessToken && $currentToken->exists && $device->personal_access_token_id === null) {
            $device->forceFill(['personal_access_token_id' => $currentToken->getKey()])->save();
        }

        return Envelope::success(
            DeviceResource::make($device)->resolve($request),
            'Device registered.',
            [],
            $device->wasRecentlyCreated ? 201 : 200
        );
    }

    /** Remove a device and revoke whatever token it holds. */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $device = $request->user()->devices()->where('uuid', $uuid)->firstOrFail();

        DB::transaction(function () use ($device) {
            if ($device->personal_access_token_id !== null) {
                PersonalAccessToken::whereKey($device->personal_access_token_id)->delete();
            }

            $device->delete();
        });

        return Envelope::success(null, 'Device removed.');
    }
}
