<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\Device;
use App\Models\Experiment;
use App\Models\Server;
use App\Models\Software;
use App\Models\UserExperiment;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Query;
use GraphQL\RawObject;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncUserExperiment
{
    /**
     * @throws BusinessLogicException
     */
    public function execute(UserExperiment $userExperiment): void
    {
        $response = $this->getServerData($userExperiment);

        if($response['status'] == 'running') return;

        if($response['status'] == 'failed') {
            $userExperiment->update(['filled' => 0]);
        } else if ($response['status'] == 'finished') {
            $userExperiment->update(['filled' => 1]);
            if(!$response['url']) return;
            $this->fetchResult($userExperiment, $response['url']);
        }
    }

    /**
     * @param UserExperiment $userExperiment
     * @param string $url
     * @return void
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    private function fetchResult(UserExperiment $userExperiment, string $url): void
    {
        $result = file_get_contents($url);
        $fileName = 'tmp/user-experiments/' . $userExperiment->id . '.csv';
        Storage::put($fileName, $result);
        $path = storage_path("app/$fileName");
        $userExperiment
            ->addMedia($path)
            ->toMediaCollection('result');
    }

    /**
     * @param UserExperiment $userExperiment
     * @return array
     * @throws BusinessLogicException
     */
    private function getServerData(UserExperiment $userExperiment): array
    {
        $server = $userExperiment->experiment->server;
        $url = 'https://' . $server->api_domain . ':' . $server->port . '/graphql';
//        $url = 'https://' . $server->api_domain . '/graphql';

        $gql = (new Query('experimentDetails'))
            ->setArguments([
                'experimentID' => $userExperiment->remote_id,
            ])
            ->setSelectionSet([
                'url',
                'status',
            ]);

        try {
            $client = new Client($url);
            $results = $client->runQuery($gql, true)->getData()['experimentDetails'];
        } catch (QueryError $exception) {
            $message = '[SyncUserExperiment | User experiment ID: ' . $userExperiment->id . '] ERROR: ' . $exception->getErrorDetails()['message'];
            Log::channel('experiment')->info($message);
            throw new BusinessLogicException($message);
        } catch (\Throwable $exception) {
            $message = '[SyncUserExperiment | User experiment ID: ' . $userExperiment->id . '] ERROR: ' . $exception->getMessage();
            Log::channel('experiment')->info($message);
            throw new BusinessLogicException($message);
        }

        return $results;
    }
}
