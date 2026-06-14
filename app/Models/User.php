<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasApiTokens, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'national_id',
        "fname",
        "lname",
        'email',
        'phone',
        'password',
        'role',
        'profile_image',
        'gender',
        'date_of_birth',
        'address',
        'email_verified_at',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'email_verified_at' => 'datetime',
        ];
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function isPatient()
    {
        return $this->role === "patient";
    }

    public function isDoctor()
    {
        return $this->role === "doctor";
    }

    // Add the missing isAdmin method
    public function isAdmin()
    {
        return $this->role === "admin";
    }

    public function getIsApprovedAttribute(): bool
    {
        return (bool) $this->attributes['is_approved'];
    }

    public function getProfile(): Patient|Doctor|null
    {
        return match ($this->role) {
            'patient' => $this->patient,
            'doctor'  => $this->doctor,
            default   => null,
        };
    }

    public function isPhoneVerified(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    public function canLogin(): array
    {
        $requirements = [

            'email_verified' => $this->isEmailVerified(),
        ];

        $canLogin = true;

        if ($this->isPatient()) {
            $canLogin =  $requirements['email_verified'];
        } elseif ($this->isDoctor()) {
            $requirements['admin_approved'] = $this->doctor?->isApproved() ?? false;
            $canLogin = $requirements['email_verified']
                && $requirements['admin_approved'];
        } elseif ($this->isAdmin()) {
            $canLogin = $requirements['email_verified'];
        }

        return [
            'can_login' => $canLogin,
            'requirements' => $requirements,
        ];
    }

    public function isEmailVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function getEmailForVerification()
    {
        return $this->email;
    }

    public function routeNotificationForMail()
    {
        return $this->email;
    }

    protected static function booted()
    {
        static::creating(function ($user) {
            if (is_null($user->profile_image) || $user->profile_image === '') {
                $user->profile_image = match ($user->role) {
                    'doctor' => 'profiles/DDoctor.png',
                    'admin'  => 'profiles/DAdmin.png',
                    default  => 'profiles/DPatient.png',
                };
            }
        });
    }

    public function getProfileImageAttribute($value)
    {
        if ($value && !empty($value)) {
            if (Storage::disk('public')->exists($value)) {
                return asset('storage/' . $value);
            }
        }

        return match ($this->role) {
            'doctor' => asset('profiles/DDoctor.png'),
            'admin'  => asset('profiles/DAdmin.png'),
            default => asset('profiles/DPatient.png'),
        };
    }
}
