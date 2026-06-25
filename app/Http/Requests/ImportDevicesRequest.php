<?php

namespace App\Http\Requests;

use App\Enums\DeviceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportDevicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'devices' => ['required', 'array', 'min:1'],
            'devices.*.name' => ['required', 'string', 'max:255'],
            'devices.*.type' => ['required', Rule::enum(DeviceType::class)],
            'devices.*.ip' => ['required', 'ip', 'distinct'],
            'devices.*.mac' => ['nullable', 'string', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'devices.*.mac.regex' => 'The MAC address must look like 1C:1B:0D:11:22:33.',
        ];
    }
}
