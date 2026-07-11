<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Http\Responses\Envelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProfileController extends Controller
{
    public function show(): JsonResponse
    {
        return Envelope::success(new ProfileResource(request()->user()));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $removeAvatar = (bool) ($data['remove_avatar'] ?? false);

        unset($data['avatar'], $data['remove_avatar']);

        $user->fill($data);
        $user->save();

        if ($removeAvatar && Schema::hasTable('media')) {
            $user->clearMediaCollection('avatar');
        }

        if ($request->hasFile('avatar') && Schema::hasTable('media')) {
            $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        }

        return Envelope::success(new ProfileResource($user->refresh()), 'Profile updated.');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->password = Hash::make($request->string('password')->toString());
        $user->save();

        return Envelope::success(null, 'Password updated successfully.');
    }
}
