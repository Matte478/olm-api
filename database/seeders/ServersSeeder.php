<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Server;
use App\Models\Software;
use Illuminate\Database\Seeder;

class ServersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $softwareOpenloop = Software::create([
           'name' => 'openloop',
        ]);
        $softwareMatlab = Software::create([
            'name' => 'matlab',
        ]);
        $softwareScilab = Software::create([
            'name' => 'scilab',
        ]);
        $softwareOpenmodelica = Software::create([
            'name' => 'openmodelica',
        ]);

        $deviceTypeHelicopter = DeviceType::create([
            'name' => 'helicopter'
        ]);
        $deviceTypeSubmatine = DeviceType::create([
            'name' => 'submarine'
        ]);

        $serverS1 = Server::create([
            'name' => 's1',
            'ip_address' => '192.168.100.100',
            'port' => '80',
            'websocket_port' => '6001',
            'available' => 1,
            'production' => 1,
            'enabled' => 1,
        ]);
        $serverS2 = Server::create([
            'name' => 's2',
            'ip_address' => '192.168.200.200',
            'port' => '80',
            'websocket_port' => '6001',
            'available' => 1,
            'production' => 1,
            'enabled' => 1,
        ]);

        $device1 = Device::create([
            'name' => 'device_1',
            'server_id' => $serverS1->id,
            'device_type_id' => $deviceTypeHelicopter->id,
        ]);
        $device1->software()->sync([
            $softwareOpenloop->id,
            $softwareMatlab->id,
            $softwareScilab->id,
            $softwareOpenmodelica->id,
        ]);

        $device2 = Device::create([
            'name' => 'device_2',
            'server_id' => $serverS2->id,
            'device_type_id' => $deviceTypeHelicopter->id,
        ]);
        $device2->software()->sync([
            $softwareOpenloop->id,
            $softwareOpenmodelica->id,
        ]);
    }
}
