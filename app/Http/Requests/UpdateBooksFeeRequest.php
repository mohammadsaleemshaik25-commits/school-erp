<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBooksFeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = strtoupper((string) optional(optional($this->user())->role)->role_name);

        // Books-fee configuration allowed for Clerk and authorized leadership
        return in_array($role, ['ADMINISTRATOR', 'ADMIN', 'CLERK', 'PRINCIPAL', 'CORRESPONDENT'], true);
    }

    public function rules(): array
    {
        return [
            'books_fee_applied' => 'required|numeric|min:0',
        ];
    }
}