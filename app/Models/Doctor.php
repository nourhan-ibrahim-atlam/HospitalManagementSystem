<?php
// app/Models/Doctor.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class Doctor extends Model
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        "user_id",
        "specialization",
        "medical_license",
        "degree_certificate",
        "professional_id_card",
        "is_approved",
        "approved_at",
        "approved_by",
        "rejection_reason"
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'is_approved' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class,'user_id');
    }

    public function emergencyVisits(): HasMany
    {
        return $this->hasMany(EmergencyVisit::class)->orderByDesc('visit_time');
    }

    public function reviewedRequests(): HasMany
    {
        return $this->hasMany(UpdateRequest::class, 'reviewed_by');
    }

    public function approvedByAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    // Helper methods to get document URLs
    public function getMedicalLicenseUrl(): ?string
    {
        return $this->medical_license ? Storage::url($this->medical_license) : null;
    }

    public function getDegreeCertificateUrl(): ?string
    {
        return $this->degree_certificate ? Storage::url($this->degree_certificate) : null;
    }

    public function getProfessionalIdCardUrl(): ?string
    {
        return $this->professional_id_card ? Storage::url($this->professional_id_card) : null;
    }

    // Scope
    public function scopeBySpecialization($query, string $spec)
    {
        return $query->where('specialization', 'like', "%{$spec}%");
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopePendingApproval($query)
    {
        return $query->where('is_approved', false);
    }

    public function getEmailForVerification()
    {
        return $this->user->email;
    }

    public function routeNotificationForMail()
    {
        return $this->user->email;
    }
}
