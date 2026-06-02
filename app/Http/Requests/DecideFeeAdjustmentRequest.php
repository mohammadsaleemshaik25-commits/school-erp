<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DecideFeeAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = strtoupper((string) optional(optional($this->user())->role)->role_name);

        // Only senior authorities can approve/reject concessions
        return in_array($role, ['ADMINISTRATOR', 'ADMIN', 'PRINCIPAL', 'CORRESPONDENT'], true);
    }

    public function rules(): array
    {
        return [
            'status'           => 'required|in:APPROVED,REJECTED',
            'decision_remarks' => 'nullable|string|max:500',
        ];
    }
}