<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Device;
use App\Models\DeviceType;
use App\Models\Experiment;
use App\Models\Schema;
use App\Models\User;
use App\Models\UserExperiment;
use App\Services\UserExperimentValidationService;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Mutation;
use GraphQL\RawObject;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use App\Models\Server;
use App\Models\Software;

class RunExperimentScript
{
    /**
     * @throws BusinessLogicException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function execute(
        Experiment $experiment, string $scriptName, array $inputs, Software $software,
        ?Schema $schema = null, ?UserExperiment $userExperiment = null
    ): UserExperiment
    {
        $user = auth()->user();
        $device = $experiment->device;
        $deviceType = $experiment->deviceType;
        $server = $experiment->server;

        app(UserExperimentValidationService::class)->validate($experiment, $scriptName, $device->id);

        $inputs = $this->formatInput($inputs, $experiment, $scriptName, $schema);

        $result = $this->runScript(
            $user, $server, $deviceType, $device, $scriptName, $inputs, $software, $schema, $userExperiment
        );

        if($result['status'] === 'error') {
            throw new BusinessLogicException($result['errorMessage'] ?? 'error');
        }

        if($userExperiment)
            $userExperiment->update([
                'input' => $this->getInputArray($scriptName, $inputs, $userExperiment->input),
                'filled' => null,
            ]);
        else {
            $simulationTime = (int) $this->getInputValue($inputs, 't_sim');
            $samplingRate = (int) $this->getInputValue($inputs, 's_rate');
            $userExperiment = UserExperiment::create([
                'user_id' => $user->id,
                'experiment_id' => $experiment->id,
                'schema_id' => $schema?->id,
                'input' => $this->getInputArray($scriptName, $inputs),
                'simulation_time' => $simulationTime,
                'sampling_rate' => $samplingRate,
                'filled' => null,
                'remote_id' => (int)$result['experimentID'],
            ]);
        }

        return $userExperiment;
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
    private function runScript(
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
                        inputParameter: "'. $this->getInputString($inputs) .'",
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
     * @param array $inputs
     * @param Experiment $experiment
     * @param string $scriptName
     * @param Schema|null $schema
     * @return mixed
     */
    private function formatInput(array $inputs, Experiment $experiment, string $scriptName, ?Schema $schema = null): array
    {
        $commandArguments = [];
        foreach ($experiment->experiment_commands as $command) {
            if($command['name'] === $scriptName) {
                $commandArguments = $command['arguments'];
                break;
            }
        }

        if($schema) {
            $schemaArguments = $schema->arguments()->get()->toArray();
            $commandArguments = array_merge($commandArguments, $schemaArguments);
        }

        foreach ($inputs as $key => &$input) {
            $processed = false;
            foreach ($commandArguments as $argument) {
                if($input['name'] === $argument['name']) {
                    $input['label'] = $argument['label'] ?? $argument['name'];
                    $processed = true;
                    break;
                }
            }

            if(!$processed)
                unset($inputs[$key]);
        }

        return $inputs;
    }

    /**
     * @param array $inputs
     * @return string
     */
    private function getInputString(array $inputs): string
    {
        $result = '';
        foreach ($inputs as $input) {
            $result .= $input['name'] . ':' . $input['value'] . ',';
        }

        return substr($result, 0, -1) ;
    }

    /**
     * @param string $command
     * @param array $actualInputs
     * @param array|null $previousInputs
     * @return array
     */
    public function getInputArray(string $command, array $actualInputs, ?array $previousInputs = []): array
    {
        $result = $previousInputs;

        foreach ($result as &$input) {
            if($input['script_name'] === $command) {
                $input['input'][] = $actualInputs;
                return $result;
            }
        }

        $result[] = [
            'script_name' => $command,
            'input' => [$actualInputs]
        ];

        return $result;
    }

    /**
     * @param array $inputs
     * @param string $inputName
     * @return string|null
     */
    private function getInputValue(array $inputs, string $inputName): ?string
    {
        foreach ($inputs as $input) {
            if($input['name'] == $inputName)
                return $input['value'];
        }

        return null;
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
