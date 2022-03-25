<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Experiment;
use App\Models\Reservation;
use Carbon\Carbon;

class UserExperimentValidationService
{
    /**
     * @throws BusinessLogicException
     */
    public function validate(Experiment $experiment, string $command, int $deviceId): void
    {
        $this->validateDeviceReservation($deviceId);
        $this->validateCommand($experiment, $command);
    }

    /**
     * @throws BusinessLogicException
     */
    public function validateDeviceReservation(int $deviceId): void
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
