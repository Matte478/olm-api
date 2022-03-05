<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Device;
use App\Models\Experiment;
use App\Models\Schema;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Query;
use GraphQL\Mutation;
use GraphQL\RawObject;
use Illuminate\Support\Facades\Log;
use App\Models\Server;
use App\Models\Software;

class RunExperimentScript
{
    public function execute(Experiment $experiment, string $scriptName, array $inputs, Software $software, ?Schema $schema = null)
    {
        $user = auth()->user();
        $device = $experiment->device;
        $deviceType = $experiment->deviceType;
        $server = $experiment->server;

        $schemaName = null;
        if($schema) {
            $schemaPath = $schema->getMedia('schema')[0]->getPath();
            $uploadResponse = $this->uploadSchema($schemaPath);
            $schemaName = $uploadResponse['name'];
        }

        $url = 'http://' . $server->ip_address . ':' . $server->port . '/graphql';

        $mutation = (new Mutation('RunScript'))
            ->setArguments([
                'runScriptInput' => new RawObject(
                    '{
                    scriptName: "' . $scriptName . '",
                    inputParameter: "' . $this->getInputString($inputs) . '",
                    fileName: "' . $schemaName . '"
                    device: {
                        deviceName: "' . $deviceType->name . '",
                        software: "' . $software->name . '",
                        deviceID: "2"
                    }
                    }'
                )
            ])
            ->setSelectionSet([
               'output'
            ]);

        try {
            $client = new Client($url);
            $result = $client->runQuery($mutation, true)->getData()['RunScript'];
        } catch (QueryError $exception) {
            $message = '[User ID: ' . $user->id . ', Device ID: ' . $device->id . '] ERROR: ' . $exception->getErrorDetails()['message'];
            Log::channel('server')->info($message);
            throw new BusinessLogicException($message);
        } catch (\Throwable $exception) {
            $message = '[User ID: ' . $user->id . ', Device ID: ' . $device->id . '] ERROR: ' . $exception->getMessage();
            Log::channel('server')->info($message);
            throw new BusinessLogicException($message);
        }

        return $result['output'];
    }

    private function getInputString(array $inputs): string
    {
        $result = '';
        foreach ($inputs as $input) {
            $result .= $input['name'] . ':' . $input['value'] . ',';
        }

        return substr($result, 0, -1) ;
    }

    private function uploadSchema(string $filePath): array
    {
        $guzzleClient = new \GuzzleHttp\Client(['base_uri' => 'http://api.exp01.iolab.sk']);
        $response = $guzzleClient->request('POST', '/api/', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r')
                ],
                [
                    'name' => 'csv_header',
                    'contents' => 'First Name, Last Name, Username',
                    'filename' => 'csv_header.csv'
                ]
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
