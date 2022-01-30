<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\Experiment;
use App\Models\Server;
use App\Models\Software;

class SyncExperiment
{
    public function execute(Server $server, Device $device, Software $software, array $commands, array $output, ?bool $hasSchema = null): Experiment
    {
        $experimentData = [
            'device_type_id' => $device->deviceType->id,
            'commands' => json_encode($this->getCommandsNames($commands)),
            'experiment_commands' => json_encode($commands),
            'output_arguments' => json_encode($output),
            'has_schema' => $hasSchema,
            'deleted_at' => null
        ];

        if(isset($hasRegulator)) {
            $experimentData['has_schema'] = $hasSchema;
        }

        return Experiment::withTrashed()->updateOrCreate([
            'server_id' => $server->id,
            'device_id' => $device->id,
            'software_id' => $software->id
        ], $experimentData);
    }

    private function getCommandsNames(array $commands): array
    {
        return array_reduce($commands, function($carry, $item) {
            $carry[] = $item['name'];
            return $carry;
        }, []);
    }
}
