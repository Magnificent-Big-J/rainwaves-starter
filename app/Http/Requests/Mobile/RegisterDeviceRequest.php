<?php

namespace App\Http\Requests\Mobile;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDeviceRequest extends FormRequest
{
    use DeviceRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->deviceRules();
    }
}
