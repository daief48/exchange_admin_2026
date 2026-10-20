<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (auth()->user()->can('manage-budgets') || auth()->user()->isHeadOffice());
    }

    public function rules(): array
    {
        return [
            'depot_id' => ['required', 'exists:depots,id'],
            'business_id' => ['nullable', 'exists:businesses,id'],
            'expense_head_id' => ['nullable', 'exists:expense_heads,id'],
            'year' => ['required', 'integer', 'min:2020', 'max:2035'],
            'month' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}
