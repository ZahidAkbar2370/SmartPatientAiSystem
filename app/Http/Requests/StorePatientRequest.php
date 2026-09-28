<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'father_husband_name' => ['nullable', 'string', 'max:150'],
            'cnic' => [
                'required',
                'string',
                'regex:/^\d{5}-\d{7}-\d$/',
                Rule::unique('patients', 'cnic'),
            ],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'document_type' => ['nullable', Rule::in(['cnic', 'driving_license'])],
            'temp_document' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Please enter patient name.',
            'cnic.required' => 'CNIC is required.',
            'cnic.regex' => 'CNIC must be in the format 35202-1234567-1.',
            'cnic.unique' => 'A patient with this CNIC already exists.',
            'date_of_birth.required' => 'Date of birth is required.',
            'date_of_birth.before' => 'Date of birth must be before today.',
            'gender.required' => 'Gender is required.',
            'address.required' => 'Address is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('cnic')) {
            $digits = preg_replace('/\D/', '', (string) $this->input('cnic'));
            if (strlen($digits) === 13) {
                $this->merge([
                    'cnic' => substr($digits, 0, 5).'-'.substr($digits, 5, 7).'-'.substr($digits, 12, 1),
                ]);
            }
        }
    }
}
