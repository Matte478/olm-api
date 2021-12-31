<?php

namespace App\Policies;

use App\Models\Device;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservationPolicy
{
    use HandlesAuthorization;

    public function show(User $user): bool
    {
        return $user->can('reservation.show');
    }

    public function create(User $user, array $injected): bool
    {
        $server = Device::findOrFail($injected['device_id'])->server;

        if(!$server->enabled) return false;

        if($server->production)
            return $user->canAny(['reservation.create_production', 'reservation.create_all']);

        return $user->can('reservation.create_all');
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->can('reservation.update_all') ||
            ($user->can('reservation.update_own') && $reservation->user_id === $user->id);
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->can('reservation.delete_all') ||
            ($user->can('reservation.delete_own') && $reservation->user_id === $user->id);
    }
}
