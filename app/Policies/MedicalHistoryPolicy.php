<?php

namespace App\Policies;

use App\Models\MedicalHistory;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MedicalHistoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MedicalHistory $medicalHistory): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        if ($user->isDoctor()) {
            $doctor = $user->doctor;
            if (!$doctor || !$doctor->isApproved()) {
                return false;
            }
            return true;
        }
        if ($user->isPatient()) {
            $patient = $user->patient;
            return $patient && $medicalHistory->patient_id === $patient->id;
        }
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user , ?int $patientId = null): bool {
         if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDoctor()) {
            $doctor = $user->doctor;
            return $doctor && $doctor->isApproved();
        }

        if ($user->isPatient() && $patientId) {
            $patient = $user->patient;
            return $patient && $patient->id === $patientId;
        }

        return false;
    }

    public function update(User $user, MedicalHistory $medicalHistory): bool
    {
                if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDoctor()) {
            $doctor = $user->doctor;
            if (!$doctor || !$doctor->isApproved()) {
                return false;
            }
            return true;
        }

        if ($user->isPatient()) {
            $patient = $user->patient;
            return $patient && $medicalHistory->patient_id === $patient->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MedicalHistory $medicalHistory): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MedicalHistory $medicalHistory): bool
    {
         return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MedicalHistory $medicalHistory): bool
    {
         return $user->isAdmin();
    }

    public function viewForPatient(User $user, Patient $patient): bool
    {

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isDoctor()) {
            $doctor = $user->doctor;
            return $doctor && $doctor->isApproved();
        }

        if ($user->isPatient()) {
            $userPatient = $user->patient;
            return $userPatient && $userPatient->id === $patient->id;
        }

        return false;
    }
}