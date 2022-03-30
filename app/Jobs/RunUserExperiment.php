<?php

namespace App\Jobs;

use App\Actions\RunUserExperimentScript;
use App\Exceptions\BusinessLogicException;
use App\Models\UserExperiment;
use App\Services\ReservationValidationService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunUserExperiment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const RETRY_INTERVAL_SECONDS = 60;
    const AVAILABILITY_THRESHOLD_SECONDS = 60;

    public UserExperiment $userExperiment;

    /**
     * Create a new job instance.
     *
     * @param UserExperiment $userExperiment
     * @return void
     */
    public function __construct(UserExperiment $userExperiment)
    {
        $this->userExperiment = $userExperiment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->userExperiment->trashed())
            $this->delete();

        $experiment = $this->userExperiment->experiment;
        $deviceType = $experiment->deviceType;
        $software = $experiment->software;

        $devices = $deviceType
            ->devices()
            ->filterSoftware($software->id)
            ->filterAvailable()
            ->withCount(['userExperiments' => function (Builder $query) {
                $query->where('created_at', '>=', Carbon::now()->addWeeks(-1));
            }])
            ->orderBy('user_experiments_count', 'ASC')
            ->get();

        foreach ($devices as $device) {
            $processingUE = $device->userExperiments()->unfinished(false)->count();
            if($processingUE) continue;

            try {
                app(ReservationValidationService::class)
                    ->validateDeviceAvailability(
                        Carbon::now()->addSeconds(self::AVAILABILITY_THRESHOLD_SECONDS * -1),
                        Carbon::now()->addSeconds($this->userExperiment->simulation_time + self::AVAILABILITY_THRESHOLD_SECONDS),
                        $device->id
                    );

                $result = app(RunUserExperimentScript::class)->execute(
                    $this->userExperiment->user, $device->server, $experiment->deviceType, $device,
                    $this->userExperiment->input[0]['script_name'], $this->userExperiment->input[0]['input'][0], $software,
                    $this->userExperiment->schema
                );

                $this->userExperiment->update([
                    'device_id' => $device->id,
                ]);

                if($result['status'] === 'error') {
                    throw new BusinessLogicException($result['errorMessage'] ?? 'error');
                }

                if($result['status'] === 'success') {
                    $this->userExperiment->update([
                        'remote_id' => (int) $result['experimentID']
                    ]);
                }

                return;

            } catch (BusinessLogicException $e) {}
        }

        RunUserExperiment::dispatch($this->userExperiment)->delay(self::RETRY_INTERVAL_SECONDS);
    }

    public function failed()
    {
        $this->userExperiment->update([
            'filled' => false,
        ]);
    }
}
