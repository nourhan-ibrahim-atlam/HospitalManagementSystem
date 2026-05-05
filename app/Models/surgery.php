<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Surgery extends Model
{
    use HasFactory;

    protected $table = 'surgeries';

    protected $fillable = [
        'patient_id',
        'surgery_name',
        'surgery_date',
        'hospital',
        'surgeon_name',
        'reason',
        'complications',
        'notes',
    ];

    protected $casts = [
        'surgery_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }
}
