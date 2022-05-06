<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Experiment;
use App\Models\Schema;
use App\Models\UserExperiment;
use App\Services\UserExperimentService;
use App\Jobs\RunUserExperiment as RunUserExperimentJob;

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
        Experiment $experiment, string $scriptName, array $inputs, ?Schema $schema = null
    ): UserExperiment
    {
        $user = auth()->user();

        $inputs = $this->userExperimentService->formatInput($inputs, $experiment, $scriptName, $schema);
        $simulationTime = (int) $this->userExperimentService->getInputValue($inputs, 't_sim');
        $samplingRate = (int) $this->userExperimentService->getInputValue($inputs, 's_rate');
        $userExperiment = UserExperiment::create([
            'user_id' => $user->id,
            'experiment_id' => $experiment->id,
            'device_id' => null,
            'schema_id' => $schema?->id,
            'input' => $this->userExperimentService->getInputArray($scriptName, $inputs),
            'simulation_time' => $simulationTime,
            'sampling_rate' => $samplingRate,
            'filled' => null,
            'remote_id' => null,
        ]);

        RunUserExperimentJob::dispatch($userExperiment);

        return $userExperiment;
    }
}
