<?php

namespace App\Http\Requests\Sync;

use App\Services\Sync\SyncRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncDeltaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'resource_list' => array_filter(array_map('trim', explode(',', (string) $this->query('resources')))),
        ]);
    }

    public function rules(): array
    {
        return [
            'since' => ['required', 'date'],
            'resources' => ['required', 'string'],
            'resource_list' => ['required', 'array', 'min:1'],
            'resource_list.*' => [Rule::in(resolve(SyncRegistry::class)->deltaResources())],
        ];
    }

    /** @return list<string> */
    public function resourceList(): array
    {
        return $this->validated('resource_list');
    }
}
