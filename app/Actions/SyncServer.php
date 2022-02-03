<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\Experiment;
use Illuminate\Support\Facades\Log;
use App\Models\Server;
use App\Models\Software;

class SyncServer
{
    public function execute(Server $server): void
    {
        Log::channel('server')->info("[Server ID: $server->id, IP: $server->ip_address] sync");

        // TODO: sync server with experimental server

        // example of response from experimental server
        $response = [
            'devices' => [
                [
                    'name' => $server->name . ' tos1a',
                    'type' => 'tos1a',
                    'output' => [
                        [
                            "name"  => 'temp_chip',
                            "title" => "Chip temp"
                        ],
                        [
                            "name"  => "f_temp_int",
                            "title" => "Filtered internal temp"
                        ]
                    ],
                    'software' => [
                        [
                            'name' => 'matlab',
                            'has_schema' => true,
                            'commands' => [
//                                https://github.com/Item21/olm_experiment_api/blob/master/config/devices/tos1a/matlab/input.php
                                [
                                    'name' => 'start',
                                    'input' => [
                                        [
                                            "name"	=>	"reg_request",
                                            "rules"	=>	"required",
                                            "title"	=>	"Žiadaná hodnota (C/lx/RPM)",
                                            "placeholder" => 30,
                                            "type"	=> "text"
                                        ],
                                        [
                                            "name"	=>	"input_fan",
                                            "rules"	=>	"required",
                                            "title"	=>	"Napätie ventilátora (0-100)",
                                            "placeholder" => 0,
                                            "type"	=>	"text"
                                        ]
                                    ]
                                ],
                                [
                                    'name' => 'change',
                                    'input' => [
                                        [
                                            "name"	=>	"reg_request",
                                            "rules"	=>	"required",
                                            "title"	=>	"Žiadaná hodnota (C/lx/RPM)",
                                            "placeholder" => 30,
                                            "type"	=>	"text"
                                        ],
                                        [
                                            "name"	=>	"input_fan",
                                            "rules"	=>	"required",
                                            "title"	=>	"Napätie ventilátora (0-100)",
                                            "placeholder" => 0,
                                            "type"	=>	"text"
                                        ],
                                        [
                                            "name"	=>	"input_led",
                                            "rules"	=>	"required",
                                            "title"	=>	"Napätie LED",
                                            "placeholder" => 0,
                                            "type"	=>	"text"
                                        ]
                                    ]
                                ],
                            ]
                        ],
                        [
                            'name' => 'openloop',
                            'has_schema' => false,
                            'commands' => [
//                                https://github.com/Item21/olm_experiment_api/blob/master/config/devices/tos1a/matlab/input.php
                                [
                                    'name' => 'start',
                                    'input' => [
                                        [
                                            "name"	=>	"reg_request",
                                            "rules"	=>	"required",
                                            "title"	=>	"Žiadaná hodnota (C/lx/RPM)",
                                            "placeholder" => 30,
                                            "type"	=> "text"
                                        ],
                                        [
                                            "name"	=>	"input_fan",
                                            "rules"	=>	"required",
                                            "title"	=>	"Napätie ventilátora (0-100)",
                                            "placeholder" => 0,
                                            "type"	=>	"text"
                                        ]
                                    ]
                                ],
                                [
                                    'name' => 'change',
                                    'input' => [
                                        [
                                            "name"	=>	"reg_request",
                                            "rules"	=>	"required",
                                            "title"	=>	"Žiadaná hodnota (C/lx/RPM)",
                                            "placeholder" => 30,
                                            "type"	=>	"text"
                                        ],
                                        [
                                            "name"	=>	"input_fan",
                                            "rules"	=>	"required",
                                            "title"	=>	"Napätie ventilátora (0-100)",
                                            "placeholder" => 0,
                                            "type"	=>	"text"
                                        ],
                                        [
                                            "name"	=>	"reg_output",
                                            "rules"	=>	"required",
                                            "title"	=>	"Regulovaná veličina",
                                            "placeholder"	=>	1,
                                            "type"	=>	"select",
                                            "values" => [
                                                [
                                                    "name" => "Teplota",
                                                    "value"=> 1
                                                ],
                                                [
                                                    "name" => "Svetlo",
                                                    "value"=> 2
                                                ],
                                                [
                                                    "name" => "Otacky",
                                                    "value"=> 3
                                                ]
                                            ]
                                        ]
                                    ]
                                ],
                            ]
                        ],
                    ]
                ]
            ]
        ];

        $server->update([
            'available' => true
        ]);

        $localDevices = $server->devices()->withTrashed()->get();
        $remoteDevices = $response['devices'];

        foreach ($localDevices as $localDevice) {
            $processed = false;
            foreach ($remoteDevices as $remoteKey => $remoteDevice) {
                if($localDevice->name === $remoteDevice['name']) {
                    if($localDevice->trashed()) $localDevice->restore();

                    $this->syncExperiments($server, $localDevice, $remoteDevice['software'], $remoteDevice['output']);

                    $softwareNames = $this->getSoftwareNames($remoteDevice['software']);
                    app(UpdateDevice::class)->execute(
                        $localDevice, $remoteDevice, $this->getSoftwareIds($softwareNames)
                    );

                    unset($remoteDevices[$remoteKey]);
                    $processed = true;
                }
            }

            // local device doesn't exists on experimental server
            if(!$processed) $localDevice->delete();
        }

        foreach ($remoteDevices as $remoteDevice) {
            $remoteDevice['server_id'] = $server->id;
            $softwareNames = $this->getSoftwareNames($remoteDevice['software']);

            $localDevice = app(CreateDevice::class)->execute(
                $remoteDevice, $this->getSoftwareIds($softwareNames)
            );

            $this->syncExperiments($server, $localDevice, $remoteDevice['software'], $remoteDevice['output']);
        }
    }

    private function syncExperiments(Server $server, Device $device, array $software, array $output): void
    {
        $experimentsIds = [];
        foreach ($software as $soft) {
            $experimentsIds[] = app(SyncExperiment::class)->execute(
                $server, $device, $this->getSoftware($soft['name']), $soft['commands'], $output,
                $soft['has_schema'] ?? true
            )->id;
        }

        Experiment::where([
            ['server_id', $server->id],
            ['device_id', $device->id]
        ])->whereNotIn('id', $experimentsIds)->delete();
    }

    private function getSoftwareNames(array $software): array
    {
        return array_reduce($software, function($carry, $item) {
            $carry[] = $item['name'];
            return $carry;
        }, []);
    }

    private function getSoftwareIds(array $softwareNames): array
    {
        $softwareIds = [];
        foreach ($softwareNames as $softwareName) {
            $softwareIds[] = $this->getSoftware($softwareName)->id;
        }

        return $softwareIds;
    }

    private function getSoftware(string $softwareName): Software
    {
        return Software::firstOrCreate(['name' => $softwareName]);
    }
}
