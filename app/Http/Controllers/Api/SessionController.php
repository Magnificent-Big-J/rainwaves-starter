<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Active *web browser* sessions (config('session.driver') === 'database'), distinct
 * from the mobile device/token list at DeviceController — a cookie-authenticated SPA
 * session never registers a Device row, and a mobile PAT request never starts a PHP
 * session, so these two "what's signed in" lists are deliberately separate surfaces.
 */
class SessionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentId = $request->hasSession() ? $request->session()->getId() : null;

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->getKey())
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($row) => $this->present($row, $currentId));

        return Envelope::success($sessions->values());
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $currentId = $request->hasSession() ? $request->session()->getId() : null;

        if ($id === $currentId) {
            return Envelope::error('You cannot revoke the session you are currently using. Sign out instead.', [], 422);
        }

        $deleted = DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', $request->user()->getKey())
            ->delete();

        abort_if($deleted === 0, 404);

        return Envelope::success(null, 'Session revoked.');
    }

    public function destroyOthers(Request $request): JsonResponse
    {
        $currentId = $request->hasSession() ? $request->session()->getId() : null;

        $query = DB::table('sessions')->where('user_id', $request->user()->getKey());

        if ($currentId) {
            $query->where('id', '!=', $currentId);
        }

        $revoked = $query->delete();

        return Envelope::success(null, "{$revoked} other session(s) revoked.");
    }

    private function present(object $row, ?string $currentId): array
    {
        return [
            'id' => $row->id,
            'ip_address' => $row->ip_address,
            'user_agent' => $row->user_agent,
            'last_active_at' => $row->last_activity ? Carbon::createFromTimestamp($row->last_activity)->toIso8601String() : null,
            'is_current' => $row->id === $currentId,
        ];
    }
}
