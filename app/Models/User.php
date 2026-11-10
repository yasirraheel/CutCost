<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'first_name',
    'last_name',
    'username',
    'birthdate',
    'phone',
    'intl_phone',
    'address1',
    'address2',
    'city',
    'zip',
    'state',
    'country',
    'referral_code',
    'language',
    'payout_low_amount',
    'payout_convert_currency',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function kycSubmission(): HasOne
    {
        return $this->hasOne(KycSubmission::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birthdate' => 'date:Y-m-d',
            'password' => 'hashed',
            'payout_low_amount' => 'boolean',
            'payout_convert_currency' => 'boolean',
        ];
    }
}
