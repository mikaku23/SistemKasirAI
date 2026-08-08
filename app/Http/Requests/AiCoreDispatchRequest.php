<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AiCoreDispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'channel_slug' => [
                'nullable',
                'string',
                'max:100',
                Rule::in([
                    'admin-core',
                    'manager-chatbot',
                    'warehouse-search',
                    'customer-service',
                ]),
            ],
            'message' => ['required', 'string', 'min:2', 'max:2000'],
            'module' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:50'],
            'payload' => ['nullable', 'array'],
            'payload_json' => ['nullable', 'string', 'max:5000'],
            'confirm' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'message.required' => 'Pesan AI wajib diisi.',
            'message.min' => 'Pesan AI minimal 2 karakter.',
            'channel_slug.in' => 'Channel AI tidak valid.',
        ];
    }
}
