<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSignalUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signal_id' => ['required', 'integer', 'exists:signals,id'],
            'update_message_fa' => ['required', 'string', 'max:500'],
            'update_message_en' => ['required', 'string', 'max:500'],
        ];
    }
}
