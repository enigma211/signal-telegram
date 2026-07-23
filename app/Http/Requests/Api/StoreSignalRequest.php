<?php

namespace App\Http\Requests\Api;

use App\Enums\MarketType;
use App\Enums\TargetAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSignalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'market_type' => ['required', Rule::enum(MarketType::class)],
            'symbol' => ['required', 'string', 'max:50'],
            'entry_price' => ['required', 'string', 'max:50'],
            'tp1' => ['required', 'string', 'max:50'],
            'tp2' => ['nullable', 'string', 'max:50'],
            'tp3' => ['nullable', 'string', 'max:50'],
            'stop_loss' => ['required', 'string', 'max:50'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'target_audience' => ['nullable', Rule::enum(TargetAudience::class)],
        ];
    }
}
