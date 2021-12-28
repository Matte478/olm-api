<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Reservation;
use Carbon\Carbon;

class ReservationValidationService
{
    /**
     * @throws BusinessLogicException
     */
    public function validate(Carbon $start, Carbon $end, int $deviceId, ?Reservation $reservation = null)
    {
        $this->validateDeviceAvailability($start, $end, $deviceId, $reservation);
        // TODO: permission for an unlimited reservation time
        $this->validateMaxReservationTime($start, $end);
        // TODO: permission for an unlimited count of reservations per day
        $this->validateMaxReservationsPerUser($start, $reservation);

        if($reservation) {
            $this->validateUpdatePossibility($reservation);
        }
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateDeviceAvailability(Carbon $start, Carbon $end, int $deviceId, ?Reservation $reservation = null)
    {
//        TODO: check maintenance reservation

        $collisions = Reservation::collidingWith($start, $end, $deviceId, $reservation?->id)->get();

        if($collisions->count()) {
            throw new BusinessLogicException('The device is reserved in the selected time range.');
        }
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateMaxReservationTime(Carbon $start, Carbon $end)
    {
        $maxReservationTime = config('reservation.max_reservation_time');

        if($start->diffInMinutes($end) > $maxReservationTime) {
            throw new BusinessLogicException("Maximum reservation time for a device is $maxReservationTime minutes.");
        }
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateMaxReservationsPerUser(Carbon $start, ?Reservation $reservation = null)
    {
        $user = auth()->user();
        $maxReservationsPerUser = config('reservation.max_reservations_per_user');

        if($user->reservations()->forDay($start, $reservation?->id)->count() >= $maxReservationsPerUser) {
            throw new BusinessLogicException('You have reached the maximum number of reservations per day.');
        }
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateUpdatePossibility(Reservation $reservation)
    {
        if($reservation->start->isPast()) {
            throw new BusinessLogicException("You cannot update an expired or ongoing reservation.");
        }
    }
}
