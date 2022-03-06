<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Experiment;
use App\Models\Schema;
use App\Models\UserExperiment;
use App\Services\UserExperimentValidationService;
use Carbon\Carbon;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
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

        app(UserExperimentValidationService::class)->validate($device->id);

        $schemaName = null;
        if($schema) {
            $schemaPath = $schema->getMedia('schema')[0]->getPath();
            $uploadResponse = $this->uploadSchema($schemaPath, $server);
            $schemaName = $uploadResponse['name'];
        }

        $url = 'http://' . $server->ip_address . ':' . $server->port . '/graphql';

        // TODO: device ID
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
            Log::channel('experiment')->info($message);
            throw new BusinessLogicException($message);
        } catch (\Throwable $exception) {
            $message = '[User ID: ' . $user->id . ', Device ID: ' . $device->id . '] ERROR: ' . $exception->getMessage();
            Log::channel('experiment')->info($message);
            throw new BusinessLogicException($message);
        }

        $userExperiment = null;
        if($result['output'] == 'success') {
            if(!isset($result['id']))
                $result['id'] = 1;

            $simulationTime = (int) $this->getInputValue($inputs,'t_sim');
            $samplingRate = (int) $this->getInputValue($inputs,'s_rate');

            $userExperiment = UserExperiment::create([
                'user_id' => $user->id,
                'experiment_id' => $experiment->id,
                'schema_id' => $schema?->id,
                'input' => $inputs,
                'simulation_time' => $simulationTime,
                'sampling_rate' => $samplingRate,
                'filled' => false,
                'remote_id' => (int) $result['id'],
            ]);
        }

        return $userExperiment;
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
     * @param array $inputs
     * @param string $inputName
     * @return string
     */
    private function getInputValue(array $inputs, string $inputName): string
    {
        foreach ($inputs as $input) {
            if($input['name'] == $inputName)
                return $input['value'];
        }

        return '';
    }

    /**
     * @param string $filePath
     * @param Server $server
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function uploadSchema(string $filePath, Server $server): array
    {
        $guzzleClient = new \GuzzleHttp\Client(['base_uri' => 'http://api.' . $server->domain . ':' . $server->port]);
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
