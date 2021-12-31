<?php

namespace App\Actions;

use App\Models\Reservation;
use App\Services\ReservationValidationService;
use Carbon\Carbon;

class CreateReservation
{
    public function execute(array $args): Reservation
    {
        app(ReservationValidationService::class)
            ->validate(new Carbon($args['start']), new Carbon($args['end']), $args['device_id']);

        return Reservation::create($args);
    }
}
