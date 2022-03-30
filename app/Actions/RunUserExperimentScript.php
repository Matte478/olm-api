<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Schema;
use App\Models\Server;
use App\Models\Software;
use App\Models\User;
use App\Models\UserExperiment;
use App\Services\UserExperimentService;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Mutation;
use GraphQL\RawObject;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;

class RunUserExperimentScript
{
    private UserExperimentService $userExperimentService;

    /**
     * @param UserExperimentService $userExperimentService
     */
    public function __construct(UserExperimentService $userExperimentService)
    {
        $this->userExperimentService = $userExperimentService;
    }

    /**
     * @param User $user
     * @param Server $server
     * @param DeviceType $deviceType
     * @param Device $device
     * @param string $scriptName
     * @param array $inputs
     * @param Software $software
     * @param Schema|null $schema
     * @param UserExperiment|null $userExperiment
     * @return array
     * @throws BusinessLogicException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(
        Authenticatable $user, Server $server, DeviceType $deviceType, Device $device, string $scriptName,
        array $inputs, Software $software, ?Schema $schema = null, ?UserExperiment $userExperiment = null
    ): array
    {
        $schemaName = null;
        if($schema && $scriptName === 'start') {
            $schemaPath = $schema->getMedia('schema')[0]->getPath();
            $uploadResponse = $this->uploadSchema($schemaPath, $server);
            $schemaName = $uploadResponse['name'];
        }

        $url = 'https://' . $server->api_domain . '/graphql';

        $mutationName = match ($scriptName) {
            'change' => 'ChangeScript',
            'stop' => 'StopScript',
            default => 'RunScript',
        };

        $mutation = (new Mutation($mutationName))
            ->setArguments([
                'runScriptInput' => new RawObject(
                    '{
                        scriptName: "'. $scriptName .'",
                        inputParameter: "'. $this->userExperimentService->getInputString($inputs) .'",
                        fileName: "'. $schemaName .'",
                        experimentID: "'. $userExperiment?->remote_id .'"
                        device: {
                            deviceName: "'. $deviceType->name .'",
                            software: "'. $software->name .'",
                            deviceID: "'. $device->remote_id .'"
                        }
                    }'
                )
            ])
            ->setSelectionSet([
                'experimentID',
                'status',
                'errorMessage',
            ]);

        try {
            $client = new Client($url);
            $result = $client->runQuery($mutation, true)->getData()[$mutationName];
        } catch (QueryError $exception) {
            $message = '[User ID: ' . $user->id . ', Device ID: ' . $device->id . ', Mutation: ' . $mutationName . '] ERROR: ' . $exception->getErrorDetails()['message'];
            Log::channel('experiment')->info($message);
            throw new BusinessLogicException($message);
        } catch (\Throwable $exception) {
            $message = '[User ID: ' . $user->id . ', Device ID: ' . $device->id . ', Mutation: ' . $mutationName . '] ERROR: ' . $exception->getMessage();
            Log::channel('experiment')->info($message);
            throw new BusinessLogicException($message);
        }

        return $result ?? [];
    }

    /**
     * @param string $filePath
     * @param Server $server
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function uploadSchema(string $filePath, Server $server): array
    {
        $guzzleClient = new \GuzzleHttp\Client(['base_uri' => 'https://' . $server->api_domain]);
        $response = $guzzleClient->request('POST', '/api/schema/upload', [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'multipart' => [
                [
                    'name' => 'file',
                    'contents' => fopen($filePath, 'r')
                ]
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
}
