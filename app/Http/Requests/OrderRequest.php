<?php

namespace App\Http\Requests;

use App\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type'           => ['required', Rule::enum(OrderType::class)],
            'weight'         => ['required', 'decimal:3', 'min:0.001'],
            'price_per_gram' => ['required', 'integer', 'min:1000']
        ];
    }
}
