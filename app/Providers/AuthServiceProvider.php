<?php
namespace App\Providers;

use App\Models\Doctor;
use App\Models\MedicalHistory;
use App\Policies\MedicalHistoryPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Patient;
use App\Policies\DoctorPolicy;
use App\Policies\PatientPolicy;
use PhpParser\Comment\Doc;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Patient::class => PatientPolicy::class,
        MedicalHistory::class =>MedicalHistoryPolicy::class ,
        Doctor::class =>DoctorPolicy::class 
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
