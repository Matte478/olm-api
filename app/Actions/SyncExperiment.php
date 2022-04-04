<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Experiment;
use App\Models\Server;
use App\Models\Software;

class SyncExperiment
{
    public function execute(
        DeviceType $deviceType, Software $software, array $commands, array $output,
        ?Server $server = null, ?Device $device = null, ?bool $hasSchema = null
    ): Experiment
    {
        $experimentData = [
            'device_type_id' => $deviceType->id,
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
            'device_type_id' => $deviceType->id,
            'software_id' => $software->id,
            'server_id' => $server?->id,
            'device_id' => $device?->id,
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
                'row' => $arg['row'],
                'order' => $arg['order'],
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
