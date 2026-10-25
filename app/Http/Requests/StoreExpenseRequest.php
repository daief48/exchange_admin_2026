<?php

namespace App\Http\Requests;

use App\Models\ExpenseHead;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $today = Carbon::today()->toDateString();

        return [
            // Strictly prohibit backdated entry: date cannot be before today
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'depot_id' => [
                'required',
                Rule::exists('depots', 'id')->where('status', true),
            ],
            'business_id' => [
                'required',
                Rule::exists('businesses', 'id')->where('status', true),
            ],
            'expense_head_id' => [
                'required',
                Rule::exists('expense_heads', 'id')->where('status', true),
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'remarks' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'voucher_attachment' => [
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:10240', // 10MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'Strict Violation: Backdated expense entries are strictly prohibited. The date must be today or current date.',
            'amount.min' => 'The expense amount must be at least 0.01 BDT.',
            'depot_id.exists' => 'The selected depot is invalid or inactive.',
            'business_id.exists' => 'The selected business unit is invalid or inactive.',
            'expense_head_id.exists' => 'The selected expense head is invalid or inactive.',
            'voucher_attachment.mimes' => 'Voucher attachment must be a PDF document or an image (jpg, jpeg, png).',
            'voucher_attachment.max' => 'Voucher file size must not exceed 10 MB.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        // If depot operator, force depot_id to their assigned depot
        if ($user && $user->isDepotOperator() && $user->depot_id) {
            $this->merge([
                'depot_id' => $user->depot_id,
            ]);
        }
    }
}
