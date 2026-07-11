<?php

namespace App\Http\Requests\Mobile;

use App\Enums\DevicePlatform;
use Illuminate\Validation\Rule;

trait DeviceRules
{
    /**
     * Validation rules for a device payload under the given key prefix
     * ('' for top-level, 'device.' when nested).
     *
     * @return array<string, array>
     */
    protected function deviceRules(string $prefix = '', bool $required = true): array
    {
        $presence = $required ? 'required' : 'sometimes';

        return [
            "{$prefix}uuid" => [$presence, 'uuid'],
            "{$prefix}platform" => [$presence, Rule::enum(DevicePlatform::class)],
            "{$prefix}model" => ['nullable', 'string', 'max:255'],
            "{$prefix}os_version" => ['nullable', 'string', 'max:255'],
            "{$prefix}app_version" => ['nullable', 'string', 'max:255'],
            "{$prefix}push_token" => ['nullable', 'string', 'max:512'],
        ];
    }
}
