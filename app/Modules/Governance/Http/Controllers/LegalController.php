<?php

namespace App\Modules\Governance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Responses\Envelope;
use App\Modules\Governance\Contracts\LegalServiceInterface;
use App\Modules\Governance\Http\Requests\AcceptLegalDocumentsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LegalController extends Controller
{
    public function __construct(
        private readonly LegalServiceInterface $legal
    ) {}

    public function status(Request $request): JsonResponse
    {
        return Envelope::success(['documents' => $this->legal->statusFor($request->user())]);
    }

    public function accept(AcceptLegalDocumentsRequest $request): JsonResponse
    {
        $this->legal->accept($request->user(), $request->validated('documents'));

        return Envelope::success(['documents' => $this->legal->statusFor($request->user())], 'Thanks — accepted.');
    }

    /** Admin-only: legal-acceptance status for a set of users, for admin/users.vue's "Legal" column. */
    public function adminSummary(Request $request): JsonResponse
    {
        $ids = collect($request->query('ids', []))->map(fn ($id) => (int) $id)->filter()->values();

        return Envelope::success(['status' => $this->legal->upToDateStatusFor($ids)]);
    }
}
