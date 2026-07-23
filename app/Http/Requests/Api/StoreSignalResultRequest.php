<?php

namespace App\Http\Requests\Api;

use App\Enums\SignalResult;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSignalResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signal_id' => ['required', 'integer', 'exists:signals,id'],
            'result' => ['required', Rule::enum(SignalResult::class)],
            'pips_gained' => ['nullable', 'integer'],
        ];
    }
}
