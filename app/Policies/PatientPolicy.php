<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PatientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        return in_array($user->role, ['admin', 'doctor', 'patient']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Patient $patient)
    {
        if ($user->role === 'admin') {
            return true;
        }
        if ($user->role === 'patient') {
            return $patient->user_id === $user->id;
        }
        if ($user->role === 'doctor') {
            return $patient->emergencyVisits()
                ->where('doctor_id', $user->doctor->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Patient $patient): bool
    {
        if ($user->role === 'admin') {
            return true;
        }
        if ($user->role === 'patient') {
            return $patient->user_id === $user->id;
        }

        if ($user->role === 'doctor') {
            return true; 
        }
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Patient $patient)
    {
        if ($user->role !== 'admin') {
            return Response::deny('Only admin can delete patients');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Patient $patient): bool
    {
        return $user->role === 'admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Patient $patient): bool
    {
        return $user->role === 'admin';
    }
}