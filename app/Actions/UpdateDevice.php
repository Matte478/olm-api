<?php

namespace App\Actions;

use App\Models\Device;
use App\Models\DeviceType;

class UpdateDevice
{
    public function execute(Device $device, array $deviceData, ?array $softwareIds): Device
    {
        $deviceType = DeviceType::firstOrCreate([
            'name' => $deviceData['type']
        ]);

        $device->update([
            'device_type_id' => $deviceType->id
        ]);

        if($softwareIds)
            $device->software()->sync($softwareIds);

        return $device;
    }
}