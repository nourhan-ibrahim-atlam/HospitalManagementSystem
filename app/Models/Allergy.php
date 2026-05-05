<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Allergy extends Model
{
    use HasFactory;

    protected $table = 'allergies';

    protected $fillable = [
        'patient_id',
        'allergen',
        'reaction',
        'severity',
        'diagnosed_date',
        'notes',
    ];

    protected $casts = [
        'diagnosed_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function isLifeThreatening()
    {
        return $this->severity === 'life_threatening';
    }

    public function isSevere()
    {
        return in_array($this->severity, ['severe', 'life_threatening']);
    }
}
