<?php
// app/Models/UpdateRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UpdateRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'update_requests';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'user_id',
        'requester_type',
        'target_type',
        'target_id',
        'field_name',
        'old_value',
        'new_value',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'reviewer_notes',
        'cancelled_by',
        'cancelled_at',
        'cancellation_reason'
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Allowed fields for patient updates
    const PATIENT_ALLOWED_FIELDS = [
        'blood_type',
        'emergency_contact',
        'phone',
        'address',
        'emergency_contact_relation'
    ];

    // Allowed fields for doctor updates
    const DOCTOR_ALLOWED_FIELDS = [
        'specialization',
        'phone',
        'office_address',
        'consultation_fee'
    ];

    // Allowed fields for user updates (common)
    const USER_ALLOWED_FIELDS = [
        'fname',
        'lname',
        'phone',
        'profile_image'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    // Requester types
    const REQUESTER_PATIENT = 'patient';
    const REQUESTER_DOCTOR = 'doctor';
    const REQUESTER_ADMIN = 'admin';

    // Target types
    const TARGET_PATIENT = 'patient';
    const TARGET_DOCTOR = 'doctor';

    /**
     * Get the patient associated with the update request
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    /**
     * Get the doctor associated with the update request
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    /**
     * Get the user who made the request
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the reviewer (admin who reviewed)
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the user who cancelled the request
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the target model (polymorphic-like)
     */
    public function target()
    {
        if ($this->target_type === 'patient') {
            return $this->belongsTo(Patient::class, 'target_id');
        }

        if ($this->target_type === 'doctor') {
            return $this->belongsTo(Doctor::class, 'target_id');
        }

        return null;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeByRequester($query, string $requesterType, int $requesterId)
    {
        return $query->where('requester_type', $requesterType)
                     ->where('user_id', $requesterId);
    }

    public function scopeByTarget($query, string $targetType, int $targetId)
    {
        return $query->where('target_type', $targetType)
                     ->where('target_id', $targetId);
    }

    // Helper methods
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function canBeReviewed(): bool
    {
        return $this->isPending();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending();
    }

    /**
     * Get allowed fields based on target type
     */
    public static function getAllowedFieldsForTarget(string $targetType): array
    {
        $fields = [];

        if ($targetType === self::TARGET_PATIENT) {
            $fields = array_merge(
                self::PATIENT_ALLOWED_FIELDS,
                self::USER_ALLOWED_FIELDS
            );
        } elseif ($targetType === self::TARGET_DOCTOR) {
            $fields = array_merge(
                self::DOCTOR_ALLOWED_FIELDS,
                self::USER_ALLOWED_FIELDS
            );
        }

        return array_unique($fields);
    }

    /**
     * Get human-readable field name
     */
    public function getFieldLabelAttribute(): string
    {
        $labels = [
            'blood_type' => 'Blood Type',
            'emergency_contact' => 'Emergency Contact',
            'emergency_contact_relation' => 'Emergency Contact Relation',
            'phone' => 'Phone Number',
            'address' => 'Address',
            'fname' => 'First Name',
            'lname' => 'Last Name',
            'profile_image' => 'Profile Image',
            'specialization' => 'Specialization',
            'office_address' => 'Office Address',
            'consultation_fee' => 'Consultation Fee'
        ];

        return $labels[$this->field_name] ?? ucfirst(str_replace('_', ' ', $this->field_name));
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            self::STATUS_PENDING => ['class' => 'warning', 'icon' => 'clock', 'text' => 'Pending Review'],
            self::STATUS_APPROVED => ['class' => 'success', 'icon' => 'check', 'text' => 'Approved & Applied'],
            self::STATUS_REJECTED => ['class' => 'danger', 'icon' => 'times', 'text' => 'Rejected'],
            self::STATUS_CANCELLED => ['class' => 'secondary', 'icon' => 'ban', 'text' => 'Cancelled'],
            default => ['class' => 'secondary', 'icon' => 'question', 'text' => 'Unknown']
        };
    }
}
