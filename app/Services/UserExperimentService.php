<?php

namespace App\Services;

use App\Models\Experiment;
use App\Models\Schema;

class UserExperimentService
{
    /**
     * @param array $inputs
     * @param Experiment $experiment
     * @param string $scriptName
     * @param Schema|null $schema
     * @return mixed
     */
    public function formatInput(array $inputs, Experiment $experiment, string $scriptName, ?Schema $schema = null): array
    {
        $commandArguments = [];
        foreach ($experiment->experiment_commands as $command) {
            if($command['name'] === $scriptName) {
                $commandArguments = $command['arguments'];
                break;
            }
        }

        if($schema) {
            $schemaArguments = $schema->arguments()->with('options')->get()->toArray();
            $commandArguments = array_merge($commandArguments, $schemaArguments);
        }

        foreach ($inputs as $key => &$input) {
            $processed = false;
            foreach ($commandArguments as $argument) {
                if($input['name'] === $argument['name']) {
                    $input['label'] = $argument['label'] ?? $argument['name'];
                    $processed = true;

                    if(isset($argument['options']) && is_array($argument['options']) && count($argument['options'])) {
                        $processed = false;
                        foreach ($argument['options'] as $option) {
                            if($input['value'] == $option['value']) {
                                $input['formatted_value'] = $option['name'];
                                $processed = true;
                            }
                        }
                    }

                    break;
                }
            }

            if(!$processed)
                unset($inputs[$key]);
        }

        return $inputs;
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
    public function getInputValue(array $inputs, string $inputName): ?string
    {
        foreach ($inputs as $input) {
            if($input['name'] == $inputName)
                return $input['value'];
        }

        return null;
    }

    /**
     * @param array $inputs
     * @return string
     */
    public function getInputString(array $inputs): string
    {
        $result = '';
        foreach ($inputs as $input) {
            $result .= $input['name'] . ':' . $input['value'] . ',';
        }

        return substr($result, 0, -1) ;
    }

}
