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
    public function validate(Carbon $start, Carbon $end, int $deviceId, ?Reservation $reservation = null): void
    {
        $user = auth()->user();

        $this->validateDeviceAvailability($start, $end, $deviceId, $reservation);

        if($user->cannot('reservation.unlimited_time'))
            $this->validateMaxReservationTime($start, $end);

        if($user->cannot('reservation.unlimited_count'))
            $this->validateMaxReservationsPerUser($start, $reservation);

        if($reservation)
            $this->validateUpdatePossibility($reservation);
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateDeviceAvailability(Carbon $start, Carbon $end, int $deviceId, ?Reservation $reservation = null): void
    {
        $this->validateDeviceMaintenance($start, $end);

        $collisions = Reservation::collidingWith($start, $end, $deviceId, $reservation?->id)->get();

        if($collisions->count()) {
            throw new BusinessLogicException('The device is reserved in the selected time range.');
        }
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateDeviceMaintenance(Carbon $start, Carbon $end): void
    {
        $maintenanceStart = config('reservation.daily_maintenance_start');
        $maintenanceEnd = config('reservation.daily_maintenance_end');

        $dailyMaintenanceStart = $start->copy()->startOfDay()
            ->addHours($maintenanceStart['hours'])
            ->addMinutes($maintenanceStart['minutes']);

        $dailyMaintenanceEnd = $start->copy()->startOfDay()
            ->addHours($maintenanceEnd['hours'])
            ->addMinutes($maintenanceEnd['minutes']);

        if(checkDatesOverlap($start, $end, $dailyMaintenanceStart, $dailyMaintenanceEnd))
            throw new BusinessLogicException('The device is maintained at the selected time range.');

        // the reservation ends on the same day
        if($start->copy()->startOfDay()->eq($end->copy()->startOfDay())) return;

        $dailyMaintenanceStart = $end->copy()->startOfDay()
            ->addHours($maintenanceStart['hours'])
            ->addMinutes($maintenanceStart['minutes']);

        $dailyMaintenanceEnd = $end->copy()->startOfDay()
            ->addHours($maintenanceEnd['hours'])
            ->addMinutes($maintenanceEnd['minutes']);

        if(checkDatesOverlap($start, $end, $dailyMaintenanceStart, $dailyMaintenanceEnd))
            throw new BusinessLogicException('The device is maintained at the selected time range.');
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateMaxReservationTime(Carbon $start, Carbon $end): void
    {
        $maxReservationTime = config('reservation.max_reservation_time');

        if($start->diffInMinutes($end) > $maxReservationTime) {
            throw new BusinessLogicException("Maximum reservation time for a device is $maxReservationTime minutes.");
        }
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateMaxReservationsPerUser(Carbon $start, ?Reservation $reservation = null): void
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
    public function validateUpdatePossibility(Reservation $reservation): void
    {
        if($reservation->start->isPast()) {
            throw new BusinessLogicException("You cannot update an expired or ongoing reservation.");
        }
    }
}
