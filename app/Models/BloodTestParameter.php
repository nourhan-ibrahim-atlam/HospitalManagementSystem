<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodTestParameter extends Model
{
    use HasFactory;

    protected $table = 'blood_test_parameters';

    protected $fillable = [
        'lab_test_id',
        'parameter_name',
        'value',
        'unit',
        'reference_range',
        'flag',
    ];

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function isAbnormal()
    {
        return in_array($this->flag, ['High', 'Low']);
    }

    public function isNormal()
    {
        return $this->flag === 'Normal';
    }
}
