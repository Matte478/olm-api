<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservationPolicy
{
    use HandlesAuthorization;

    public function show(User $user)
    {
        return $user->can('reservation.show');
    }

    public function create(User $user)
    {
        return $user->can('reservation.create');
    }

    public function update(User $user, Reservation $reservation)
    {
        return $user->can('reservation.update_all') ||
            ($user->can('reservation.update_own') && $reservation->user_id === $user->id);
    }

    public function delete(User $user, Reservation $reservation)
    {
        return $user->can('reservation.delete_all') ||
            ($user->can('reservation.delete_own') && $reservation->user_id === $user->id);
    }
}
