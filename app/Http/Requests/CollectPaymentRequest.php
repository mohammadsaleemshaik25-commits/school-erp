<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CollectPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = strtoupper((string) optional(optional($this->user())->role)->role_name);

        // Fee collection allowed for Clerk, Principal, Correspondent, and Administrator
        return in_array($role, ['ADMINISTRATOR', 'ADMIN', 'CLERK', 'PRINCIPAL', 'CORRESPONDENT'], true);
    }

    public function rules(): array
    {
        return [
            'account_id'             => 'required|integer|exists:student_fee_accounts,account_id',
            'amount'                 => 'required|numeric|min:0.01',
            'payment_mode'           => 'required|string|in:CASH,UPI',
            'transaction_reference'  => 'nullable|string|max:100|required_if:payment_mode,UPI',
            'books_purchased'        => 'nullable|string|in:yes,no',
            'allocation'             => 'nullable|string|in:BOOKS,TUITION,PREVIOUS',
            'overpayment_allocation' => 'nullable|string|in:BOOKS,TUITION,PREVIOUS',
            'allocations'            => 'nullable|array',
            'allocations.*'          => 'required|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_reference.required_if' => 'A transaction reference / UPI reference code is required for UPI payments.',
        ];
    }
}