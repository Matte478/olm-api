<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Experiment;
use App\Models\Reservation;
use Carbon\Carbon;

class UserExperimentValidationService
{
    const AVAILABILITY_THRESHOLD_SECONDS = 60;

    /**
     * @throws BusinessLogicException
     */
    public function validate(Experiment $experiment, string $command, ?int $deviceId = null, ?int $simulationTime = null): void
    {
        if($deviceId)
            $this->validateDeviceReservation($deviceId, $simulationTime);

        $this->validateCommand($experiment, $command);
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateDeviceReservation(int $deviceId, ?int $simulationTime = null): void
    {
        $user = auth()->user();
        $now = Carbon::now();

        $reservation = Reservation::where([
            ['user_id', $user->id],
            ['device_id', $deviceId],
            ['start', '<=', $now],
            ['end', '>=', $now]
        ])->first();

        if(!$reservation)
            throw new BusinessLogicException('The device is not reserved.');

        if($simulationTime)
            $this->validateExperimentDuration($reservation, $simulationTime);
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateExperimentDuration(Reservation $reservation, int $simulationTime): void
    {
        $experimentEnd = Carbon::now()->addSeconds($simulationTime + self::AVAILABILITY_THRESHOLD_SECONDS);

        if ($experimentEnd > $reservation->end)
            throw new BusinessLogicException('The experiment cannot be run at the reserved time.');
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateCommand(Experiment $experiment, $command)
    {
        if (!in_array($command, $experiment->commands))
            throw new BusinessLogicException('The command does not exist.');
    }
}
