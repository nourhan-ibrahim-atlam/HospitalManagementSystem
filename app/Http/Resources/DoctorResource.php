<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
public function toArray($request)
{
    return [
        'id' => $this->id,
        'specialization' => $this->specialization,
        'is_approved' => $this->is_approved,
        'approved_at' => $this->approved_at,

        'user' => [
            'id' => $this->user->id,
            'fname' => $this->user->fname,
            'lname' => $this->user->lname,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'national_id' => $this->user->national_id,
            'role' => $this->user->role,
            'gender' => $this->user->gender,
            'date_of_birth' => $this->user->date_of_birth,
            'address' => $this->user->address,
            'profile_image' => $this->user->profile_image,
            'email_verified_at' => $this->user->email_verified_at,
            'created_at' => $this->user->created_at,
        ],
    ];
}
}
