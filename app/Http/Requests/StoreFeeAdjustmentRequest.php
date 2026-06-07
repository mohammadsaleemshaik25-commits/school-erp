<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeeAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = strtoupper((string) optional(optional($this->user())->role)->role_name);

        // Any finance-facing role can raise a concession request
        return in_array($role, ['ADMINISTRATOR', 'ADMIN', 'CLERK', 'PRINCIPAL', 'CORRESPONDENT'], true);
    }

    public function rules(): array
    {
        return [
            'account_id'      => 'required|exists:student_fee_accounts,account_id',
            'adjustment_type' => 'required|in:CONCESSION,WAIVER',
            'discount_amount' => 'required_without:discount_percent|nullable|numeric|min:0',
            'discount_percent' => 'required_without:discount_amount|nullable|numeric|min:0|max:100',
            'reason'          => 'required|string|min:5|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'discount_amount.required_without' => 'Either Discount Amount or Discount Percentage is required.',
            'discount_percent.required_without' => 'Either Discount Amount or Discount Percentage is required.',
        ];
    }
}