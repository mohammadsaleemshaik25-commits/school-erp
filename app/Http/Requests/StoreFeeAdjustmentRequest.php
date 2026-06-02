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
            'student_fee_account_id' => 'required|exists:student_fee_accounts,id',
            'adjustment_type'        => 'required|in:CONCESSION,WAIVER,PREVIOUS_BALANCE_WAIVER',
            'sub_type'               => 'required|in:SIBLING_DISCOUNT,MERIT_SCHOLARSHIP,SPECIAL_CONCESSION,BALANCE_WAIVER',
            'amount'                 => 'required|numeric|min:0.01',
            'reason'                 => 'required|string|min:5|max:500',
        ];
    }
}