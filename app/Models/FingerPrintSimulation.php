<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FingerPrintSimulation extends Model
{
    use HasFactory;

    protected $table = 'fingerprint_simulation';
     protected $primaryKey = 'id';

    protected $fillable = ['patient_id', 'fingerprint_code'];



        public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'id');
    }


    public static function generateCode(): string
    {
        return Str::upper(Str::random(32));
    }

}
