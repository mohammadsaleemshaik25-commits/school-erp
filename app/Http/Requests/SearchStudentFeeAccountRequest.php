<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SearchStudentFeeAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to perform this search.
     * Restricts capabilities to authorized ERP staff.
     */
    public function authorize(): bool
    {
        $role = strtoupper((string) optional(optional($this->user())->role)->role_name);

        return in_array($role, ['ADMINISTRATOR', 'ADMIN', 'CLERK', 'PRINCIPAL', 'CORRESPONDENT'], true);
    }

    /**
     * Get the validation rules that apply to the search request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Optional: Filter the search within a specific academic year
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],

            // Search by admission number
            'admission_no' => [
                'nullable', 
                'string', 
                'max:30', 
                'required_without_all:student_name,class_id,section_id'
            ],

            // Search by student name
            'student_name' => [
                'nullable', 
                'string', 
                'max:100', 
                'required_without_all:admission_no,class_id,section_id'
            ],

            // Filter by specific class
            'class_id' => [
                'nullable', 
                'integer', 
                'exists:classes,class_id', 
                'required_without_all:admission_no,student_name,section_id'
            ],

            // Filter by specific section, verified to belong to the selected class if specified
            'section_id' => [
                'nullable',
                'integer',
                Rule::exists('sections', 'section_id')->where(function ($query) {
                    return $query->when($this->filled('class_id'), function ($query) {
                        return $query->where('class_id', $this->integer('class_id'));
                    });
                }),
                'required_without_all:admission_no,student_name,class_id',
            ],
        ];
    }

    /**
     * Get customized error messages for the dynamic fields.
     */
    public function messages(): array
    {
        return [
            'admission_no.required_without_all' => 'Please provide at least one search criteria: Admission Number, Student Name, Class, or Section.',
            'student_name.required_without_all' => 'Please provide at least one search criteria: Admission Number, Student Name, Class, or Section.',
            'class_id.required_without_all'     => 'Please provide at least one search criteria: Admission Number, Student Name, Class, or Section.',
            'section_id.required_without_all'   => 'Please provide at least one search criteria: Admission Number, Student Name, Class, or Section.',
        ];
    }
}