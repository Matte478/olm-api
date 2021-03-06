<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Joselfonseca\LighthouseGraphQLPassport\HasSocialLogin;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles, HasSocialLogin;

    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'deleted_at'
    ];

    // TODO: use https://github.com/michaeldyrynda/laravel-cascade-soft-deletes
    public static function boot()
    {
        parent::boot();

        self::deleting(function (User $user) {
            foreach ($user->reservations as $reservation) {
                $reservation->delete();
            }
        });
    }


    public function getPermissionsListAttribute()
    {
        return $this->getAllPermissions()->pluck('name');
    }

    // **************************** RELATIONS **************************** //

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }
}
