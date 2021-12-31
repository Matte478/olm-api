<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Reservation;
use App\Services\ReservationValidationService;
use Carbon\Carbon;

class UpdateReservation
{
    public function execute(Reservation $reservation, array $args): Reservation
    {
        app(ReservationValidationService::class)
            ->validate(new Carbon($args['start']), new Carbon($args['end']), $reservation->device_id, $reservation);

        $reservation->update($args);

        return $reservation;
    }
}
