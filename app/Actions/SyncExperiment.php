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
            'commands' => $this->getCommandsNames($commands),
            'experiment_commands' => $this->formatCommands($commands),
            'output_arguments' => $output,
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

    private function formatCommands(array $commands): array
    {
        $formattedCommands = [];
        foreach ($commands as $command) {
            $formattedCommands[] = [
                'name' => $command['name'],
                'arguments' => $this->formatArguments($command['input'])
            ];
        }

        return $formattedCommands;
    }

    private function formatArguments(array $arguments): array
    {
        $formattedArguments = [];
        foreach ($arguments as $arg) {
            $formatted = [
                'name' => $arg['name'],
                'label' => $arg['title'],
                'default_value' => $arg['placeholder'],
            ];

            if(isset($arg['values'])) {
                $options = [];
                foreach ($arg['values'] as $option) {
                    $options[] = [
                        'name' => $option['name'],
                        'value' => $option['value'],
                    ];
                }

                $formatted['options'] = $options;
            }

            $formattedArguments[] = $formatted;
        }

        return $formattedArguments;
    }
}
