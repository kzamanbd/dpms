<?php

namespace App\Http\Requests;

use App\Enums\DeviceType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeviceRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(DeviceType::class)],
            'ip' => ['required', 'ip'],
            'mac' => ['nullable', 'string', 'regex:/^([0-9A-Fa-f]{2}[:-]){5}[0-9A-Fa-f]{2}$/'],
            'vlan' => ['nullable', 'string', 'max:50'],
            'monitor_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'pjlink_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'pjlink_password' => ['nullable', 'string', 'max:255'],
            'wol_broadcast' => ['nullable', 'string', 'max:255'],
            'wol_port' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mac.regex' => 'The MAC address must look like 1C:1B:0D:11:22:33.',
        ];
    }
}
