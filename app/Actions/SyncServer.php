<?php

namespace App\Actions;

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
                    'name' => $server->name . ' test device',
                    'type' => 'helicopter',
                    'software' => [
                        'matlab',
                        'testovaci-typ'
                    ]
                ],
                [
                    'name' => $server->name . ' test device 2',
                    'type' => 'submarine',
                    'software' => [
                        'matlab',
                        'scilab'
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

                    app(UpdateDevice::class)->execute(
                        $localDevice, $remoteDevice, $this->getSoftwareIds($remoteDevice['software'])
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
            app(CreateDevice::class)->execute(
                $remoteDevice, $this->getSoftwareIds($remoteDevice['software'])
            );
        }
    }

    private function getSoftwareIds(array $softwareNames): array
    {
        $softwareIds = [];
        foreach ($softwareNames as $softwareName) {
            array_push($softwareIds, Software::firstOrCreate(['name' => $softwareName])->id);
        }

        return $softwareIds;
    }
}