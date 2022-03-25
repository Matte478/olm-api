<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Device;
use App\Models\Experiment;
use App\Models\Schema;
use App\Models\Software;
use App\Models\UserExperiment;
use App\Services\ReservationValidationService;
use App\Services\UserExperimentService;
use Carbon\Carbon;

class QueueUserExperiment
{
    private UserExperimentService $userExperimentService;

    /**
     * @param UserExperimentService $userExperimentService
     */
    public function __construct(UserExperimentService $userExperimentService)
    {
        $this->userExperimentService = $userExperimentService;
    }

    /**
     * @throws BusinessLogicException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(
        Experiment $experiment, string $scriptName, array $inputs, Software $software, ?Schema $schema = null
    )
    {
        $deviceType = $experiment->deviceType;
        $software = $experiment->software;

        $simulationTime = (int) $this->userExperimentService->getInputValue($inputs, 't_sim');

        $devices = $deviceType->devices()->filterSoftware($software->id)->get();

        $threshold = 60;
        foreach ($devices as $device) {
            $runningUE = $device->userExperiment()->unfinished(false)->count();
            if($runningUE) continue;

            try {
                app(ReservationValidationService::class)
                    ->validateDeviceAvailability(
                        Carbon::now()->addSeconds($threshold * -1),
                        Carbon::now()->addSeconds($simulationTime + $threshold),
                        $device->id
                    );

                $this->run($device, $experiment, $scriptName, $inputs, $software, $schema);
            } catch (BusinessLogicException $e) {}
        }

        dd($devices);
    }

    /**
     * @param Device $device
     * @param Experiment $experiment
     * @param string $scriptName
     * @param array $inputs
     * @param Software $software
     * @param Schema|null $schema
     * @return string
     * @throws BusinessLogicException
     */
    public function run(Device $device, Experiment $experiment, string $scriptName, array $inputs, Software $software, ?Schema $schema = null)
    {
        $user = auth()->user();

        $inputs = $this->userExperimentService->formatInput($inputs, $experiment, $scriptName, $schema);
        $simulationTime = (int) $this->userExperimentService->getInputValue($inputs, 't_sim');
        $samplingRate = (int) $this->userExperimentService->getInputValue($inputs, 's_rate');

        $result = app(RunUserExperimentScript::class)->execute(
            $user, $device->server, $experiment->deviceType, $device, $scriptName, $inputs, $software, $schema
        );

        if($result['status'] === 'error') {
            throw new BusinessLogicException($result['errorMessage'] ?? 'error');
        }

        UserExperiment::create([
            'user_id' => $user->id,
            'experiment_id' => $experiment->id,
            'device_id' => $device->id,
            'schema_id' => $schema?->id,
            'input' => $this->userExperimentService->getInputArray($scriptName, $inputs),
            'simulation_time' => $simulationTime,
            'sampling_rate' => $samplingRate,
            'filled' => null,
            'remote_id' => (int)$result['experimentID'],
        ]);

        return 'done';
    }
}
