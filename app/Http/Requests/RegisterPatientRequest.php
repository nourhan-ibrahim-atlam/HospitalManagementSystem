<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Http\FormRequest;

class RegisterPatientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'national_id' => 'required|string|max:20|unique:users,national_id',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => ['required', 'confirmed', Password::min(8)],
            'blood_type' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'emergency_contact' => 'nullable|string|max:20',
            'email' => 'required|email|max:255|unique:users,email',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date',
            'address' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'fname.required' => 'First name is required.',
            'fname.string' => 'First name must be text.',
            'fname.max' => 'First name must not exceed 255 characters.',

            'lname.required' => 'Last name is required.',
            'lname.string' => 'Last name must be text.',
            'lname.max' => 'Last name must not exceed 255 characters.',

            'national_id.required' => 'National ID is required.',
            'national_id.string' => 'National ID must be valid.',
            'national_id.max' => 'National ID must not exceed 20 characters.',
            'national_id.unique' => 'This National ID is already registered.',

            'phone.required' => 'Phone number is required.',
            'phone.string' => 'Phone must be valid.',
            'phone.max' => 'Phone must not exceed 20 characters.',
            'phone.unique' => 'This phone number is already registered.',

            'email.email' => 'Email must be a valid email address.',
            'email.max' => 'Email must not exceed 255 characters.',
            'email.unique' => 'This email is already registered.',

            'password.required' => 'Password is required.',
            'password.confirmed' => 'Passwords do not match.',
            'password.min' => 'Password must be at least 8 characters.',

            'blood_type.in' => 'Invalid blood type selected.',
            'emergency_contact.max' => 'Emergency contact must not exceed 20 characters.',
            'gender.in' => 'Gender must be male or female.',
            'date_of_birth.date' => 'Date of birth must be a valid date.',
            'address.max' => 'Address must not exceed 500 characters.',
        ];
    }
}
