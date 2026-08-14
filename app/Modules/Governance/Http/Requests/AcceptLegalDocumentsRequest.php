<?php

namespace App\Modules\Governance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcceptLegalDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1'],
            'documents.*' => ['required', 'string', 'in:'.implode(',', array_keys(config('governance.legal_versions', [])))],
        ];
    }
}
