<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Experiment;
use App\Models\Schema;
use App\Models\UserExperiment;
use App\Services\UserExperimentService;
use App\Services\UserExperimentValidationService;
use App\Models\Software;

class RunUserExperiment
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
        Experiment $experiment, string $scriptName, array $inputs, Software $software,
        ?Schema $schema = null, ?UserExperiment $userExperiment = null
    ): UserExperiment
    {
        $user = auth()->user();
        $device = $experiment->device;
        $deviceType = $experiment->deviceType;
        $server = $experiment->server;

        if(!$device)
            throw new BusinessLogicException('The device must be defined.');

        $simulationTime = null;
        if(!$userExperiment) {
            $simulationTime = (int) $this->userExperimentService->getInputValue($inputs, 't_sim');
        }

        app(UserExperimentValidationService::class)->validate($experiment, $scriptName, $device->id, $simulationTime);

        $inputs = $this->userExperimentService->formatInput($inputs, $experiment, $scriptName, $schema);

        $result = app(RunUserExperimentScript::class)->execute(
            $user, $server, $deviceType, $device, $scriptName, $inputs, $software, $schema, $userExperiment
        );

        if($result['status'] === 'error') {
            throw new BusinessLogicException($result['errorMessage'] ?? 'error');
        }

        if($userExperiment)
            $userExperiment->update([
                'input' => $this->userExperimentService->getInputArray($scriptName, $inputs, $userExperiment->input),
                'filled' => null,
            ]);
        else {
            $samplingRate = (int) $this->userExperimentService->getInputValue($inputs, 's_rate');
            $userExperiment = UserExperiment::create([
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
        }

        return $userExperiment;
    }
}
