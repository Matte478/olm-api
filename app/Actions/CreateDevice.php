<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\DeviceType;

class CreateDevice
{
    public function execute(array $deviceData, ?array $softwareIds): Device
    {
        $deviceType = DeviceType::firstOrCreate([
            'name' => $deviceData['type']
        ]);

        $device = Device::create([
            'name' => $deviceData['name'],
            'server_id' => $deviceData['server_id'],
            'device_type_id' => $deviceType->id
        ]);

        if ($softwareIds)
            $device->software()->sync($softwareIds);

        return $device;
    }
}