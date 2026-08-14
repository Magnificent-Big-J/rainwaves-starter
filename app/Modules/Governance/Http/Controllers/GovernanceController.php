<?php

namespace App\Modules\Governance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Modules\Governance\Contracts\GovernanceServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GovernanceController extends Controller
{
    public function __construct(
        private readonly GovernanceServiceInterface $governance
    ) {}

    public function exportData(Request $request): StreamedResponse
    {
        $data = $this->governance->exportDataFor($request->user());

        return response()->streamDownload(
            fn () => print (json_encode($data, JSON_PRETTY_PRINT)),
            'my-data.json',
            ['Content-Type' => 'application/json'],
        );
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            $this->governance->deleteOwnAccount($user);
        } catch (RuntimeException $exception) {
            return Envelope::error($exception->getMessage(), [], 422);
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return Envelope::success(null, 'Your account has been deleted.');
    }
}
