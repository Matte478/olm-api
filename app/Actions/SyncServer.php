<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Experiment;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Query;
use Illuminate\Support\Facades\Log;
use App\Models\Server;
use App\Models\Software;

class SyncServer
{
    protected Server $server;

    /**
     * @param Server $server
     * @throws BusinessLogicException
     */
    public function execute(Server $server): void
    {
        $this->server = $server;

        Log::channel('server')->info("[Server ID: $server->id, IP: $server->ip_address] sync");

        try {
            $response = $this->getServerData();
        } catch (BusinessLogicException $exception) {
            $server->update([
                'available' => false,
                'enabled' => false,
            ]);
            throw $exception;
        }

        $server->update(['available' => true]);

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

        $this->syncDeviceTypeExperiments($response['devices']);
    }

    /**
     * @param array $remoteDevices
     * @return void
     */
    private function syncDeviceTypeExperiments(array $remoteDevices): void
    {
        $deviceTypeExperiments = [];
        foreach ($remoteDevices as $remoteDevice) {
            $type = $remoteDevice['type'];
            $deviceTypeExperiments[$type]['output'] = $remoteDevice['output'];
            if(!isset($deviceTypeExperiments[$type]['software']))
                $deviceTypeExperiments[$type]['software'] = [];

            foreach ($remoteDevice['software'] as $soft) {
                $exists = false;
                foreach ($deviceTypeExperiments[$type]['software'] as $localSoft) {
                    if ($soft['name'] === $localSoft['name']) {
                        $exists = true;
                        break;
                    }
                }

                if(!$exists)
                    $deviceTypeExperiments[$type]['software'][] = $soft;
            }
        }

        foreach ($deviceTypeExperiments as $deviceType => $deviceTypeExperiment) {
            $deviceType = $this->getDeviceType($deviceType);

            foreach ($deviceTypeExperiment['software'] as $soft) {
                app(SyncExperiment::class)->execute(
                    $deviceType, $this->getSoftware($soft['name']), $soft['commands'], $deviceTypeExperiment['output'],
                    null, null, $soft['has_schema'] ?? true
                );
            }
        }

        $this->cleanupDeviceTypeExperiments();
    }

    /**
     * @return void
     */
    public function cleanupDeviceTypeExperiments(): void
    {
        $experiments = Experiment::where([
            'server_id' => null,
            'device_id' => null
        ])->get();

        foreach ($experiments as $experiment) {
            $deviceType = $experiment->deviceType;
            $software = $experiment->software;

            $devicesCount = $deviceType->devices()->filterSoftware($software->id)->count();

            // device of the required type with the required software does not exist
            if(!$devicesCount)
                $experiment->delete();
        }
    }

    /**
     * @param Server $server
     * @param Device $device
     * @param array $software
     * @param array $output
     * @return void
     */
    private function syncExperiments(Server $server, Device $device, array $software, array $output): void
    {
        $experimentsIds = [];
        foreach ($software as $soft) {
            $experimentsIds[] = app(SyncExperiment::class)->execute(
                $device->deviceType, $this->getSoftware($soft['name']), $soft['commands'], $output, $server, $device,
                $soft['has_schema'] ?? true
            )->id;
        }

        Experiment::where([
            ['server_id', $server->id],
            ['device_id', $device->id]
        ])->whereNotIn('id', $experimentsIds)->delete();
    }

    /**
     * @param array $software
     * @return array
     */
    private function getSoftwareNames(array $software): array
    {
        return array_reduce($software, function($carry, $item) {
            $carry[] = $item['name'];
            return $carry;
        }, []);
    }

    /**
     * @param array $softwareNames
     * @return array
     */
    private function getSoftwareIds(array $softwareNames): array
    {
        $softwareIds = [];
        foreach ($softwareNames as $softwareName) {
            $softwareIds[] = $this->getSoftware($softwareName)->id;
        }

        return $softwareIds;
    }

    /**
     * @param string $softwareName
     * @return Software
     */
    private function getSoftware(string $softwareName): Software
    {
        return Software::firstOrCreate(['name' => $softwareName]);
    }

    /**
     * @param string $deviceTypeName
     * @return DeviceType
     */
    private function getDeviceType(string $deviceTypeName): DeviceType
    {
        return DeviceType::firstOrCreate(['name' => $deviceTypeName]);
    }

    /**
     * @return array
     * @throws BusinessLogicException
     */
    private function getServerData(): array
    {
        $url = 'https://' . $this->server->api_domain . '/graphql';

        $gql = (new Query('SyncServer'))
            ->setSelectionSet([
                (new Query('devices'))
                    ->setSelectionSet([
                        'id',
                        'name',
                        'type',
                        (new Query('output'))
                            ->setSelectionSet([
                                'name',
                                'title',
                            ]),
                        (new Query('software'))
                            ->setSelectionSet([
                                'name',
                                'has_schema',
                                (new Query('commands'))
                                    ->setSelectionSet([
                                        'name',
                                        (new Query('input'))
                                            ->setSelectionSet([
                                                'name',
                                                'rules',
                                                'title',
                                                'placeholder',
                                                'type',
                                                'row',
                                                'order',

                                                (new Query('options'))
                                                    ->setSelectionSet([
                                                        'name',
                                                        'value',
                                                    ]),
                                            ]),
                                    ])
                            ])
                    ])
            ]);

        try {
            $client = new Client($url);
            $results = $client->runQuery($gql, true)->getData()['SyncServer'];
        } catch (QueryError $exception) {
            $message = '[Server IP: ' . $this->server->ip_address . '] ERROR: ' . $exception->getErrorDetails()['message'];
            Log::channel('server')->info($message);
            throw new BusinessLogicException($message);
        } catch (\Throwable $exception) {
            $message = '[Server IP: ' . $this->server->ip_address . '] ERROR: ' . $exception->getMessage();
            Log::channel('server')->info($message);
            throw new BusinessLogicException($message);
        }

        return $results;
    }
}
