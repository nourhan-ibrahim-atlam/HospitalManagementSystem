<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fname' => 'required|string|max:255',
            'lname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'national_id' => 'required|string|unique:users,national_id',
            'password' => 'required|string|min:8|confirmed',
            'gender' => 'required|in:male,female',
            'date_of_birth' => 'required|date',
            'address' => 'required|string|max:500',
            'specialization' => 'required|string|in:General Practitioner,Cardiology,Dermatology,Neurology,Pediatrics,Orthopedics,Gynecology,Ophthalmology,ENT,Urology,Psychiatry,Oncology,Radiology,Anesthesiology,Gastroenterology,Endocrinology,Pulmonology,Nephrology,Hematology,Infectious Diseases,Rheumatology,Plastic Surgery,Emergency Medicine,Family Medicine',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'medical_license' => 'required|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'degree_certificate' => 'required|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'professional_id_card' => 'required|file|mimes:pdf,jpeg,png,jpg|max:5120',
        ];
    }
}
