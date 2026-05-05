<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany, HasOne};
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'blood_type',
        'emergency_contact'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicalHistories()
    {
        return $this->hasMany(MedicalHistory::class)->latest();
    }
    public function updateRequests(): HasMany
    {
        return $this->hasMany(UpdateRequest::class)->latest();
    }

    public function emergencyVisits(): HasMany
    {
        return $this->hasMany(EmergencyVisit::class)->orderByDesc('visit_time');
    }




    public function scopeByBloodType($query, string $type)
    {
        return $query->where('blood_type', $type);
    }
     public function fingerprint(): HasOne
    {
        return $this->hasOne(FingerPrintSimulation::class, 'patient_id', 'id');
    }

}