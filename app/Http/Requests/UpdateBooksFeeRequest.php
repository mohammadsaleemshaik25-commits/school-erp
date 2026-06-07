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
            'books_status' => 'required|in:SCHOOL,OUTSIDE',
            'student_name_confirmation' => 'required|string',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $accountId = $this->route('accountId');
            $account = \App\Models\StudentFeeAccount::with('enrollment.student')->find($accountId);
            
            if ($account && strtoupper($this->student_name_confirmation) !== strtoupper($account->enrollment->student->student_name)) {
                $validator->errors()->add('student_name_confirmation', 'Student name does not match. Please type the full name exactly as shown.');
            }
        });
    }
}