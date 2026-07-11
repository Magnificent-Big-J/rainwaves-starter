<?php

namespace App\Http\Requests\Sync;

use Illuminate\Foundation\Http\FormRequest;

class SyncOperationsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'operations' => ['required', 'array', 'min:1', 'max:'.config('sync.batch_max', 100)],
            'operations.*.id' => ['required', 'uuid', 'distinct'],
            'operations.*.type' => ['required', 'string', 'max:50'],
            // Unknown resources fail per-op (not the whole batch) so one bad
            // op can't wedge the client's offline queue.
            'operations.*.resource' => ['required', 'string', 'max:100'],
            'operations.*.client_id' => ['nullable', 'uuid'],
            'operations.*.resource_id' => ['nullable', 'string', 'max:255'],
            'operations.*.payload' => ['sometimes', 'array'],
            'operations.*.occurred_at' => ['required', 'date'],
            'operations.*.depends_on' => ['sometimes', 'array'],
            'operations.*.depends_on.*' => ['uuid'],
        ];
    }
}
