<?php

namespace App\Actions;

use App\Exceptions\BusinessLogicException;
use App\Models\UserExperiment;
use GraphQL\Client;
use GraphQL\Exception\QueryError;
use GraphQL\Query;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SyncUserExperiment
{
    /**
     * @param UserExperiment $userExperiment
     * @throws BusinessLogicException
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist
     * @throws \Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig
     */
    public function execute(UserExperiment $userExperiment): void
    {
        if(!$userExperiment->remote_id) return;

        $response = $this->getServerData($userExperiment);

        if($response['status'] == 'running') return;

        if($response['status'] == 'failed') {
            $userExperiment->update(['filled' => 0]);
        } else if ($response['status'] == 'finished') {
            if($response['url'])
                $this->fetchResult($userExperiment, $response['url']);
            else
                $userExperiment->update(['filled' => 0]);
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

        $data = $this->readCSV($path);
        $userExperiment->update([
            'filled' => $data && count($data[0]['data']) ? 1 : 0, // if we don't have the measured values, then there was an error in the processing
            'output' => $data
        ]);

        $userExperiment
            ->addMedia($path)
            ->toMediaCollection('result');
    }

    /**
     * @param $csvFile
     * @return array
     */
    private function readCSV($csvFile): array
    {
        $fileHandler = fopen($csvFile, 'r');
        $headers = fgetcsv($fileHandler, 0, ',');

        $data = [];
        foreach ($headers as $header) {
           $data[] = [
               'name' => $header,
               'data' => []
           ];
        }

        while (($line = fgetcsv($fileHandler, 0, ',')) !== false) {
            foreach ($line as $index => $value) {
                if(is_null($value)) continue;

                $data[$index]['data'][] = $value;
            }
        }
        fclose($fileHandler);
        return $data;
    }

    /**
     * @param UserExperiment $userExperiment
     * @return array
     * @throws BusinessLogicException
     */
    private function getServerData(UserExperiment $userExperiment): array
    {
        $server = $userExperiment->device->server;
        $url = 'https://' . $server->api_domain . '/graphql';

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
