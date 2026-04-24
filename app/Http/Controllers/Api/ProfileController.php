<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ProfileController extends Controller
{
    public function show(): ProfileResource
    {
        return new ProfileResource(request()->user());
    }

    public function update(UpdateProfileRequest $request): ProfileResource
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

        return new ProfileResource($user->refresh());
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->password = Hash::make($request->string('password')->toString());
        $user->save();

        return response()->json([
            'status' => 'ok',
            'message' => 'Password updated successfully.',
        ]);
    }
}
