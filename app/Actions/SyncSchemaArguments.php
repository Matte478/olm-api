<?php

namespace App\Actions;

use App\Models\Argument;
use App\Models\Option;
use App\Models\Schema;

class SyncSchemaArguments
{
    public function execute(Schema $schema, array $arguments): void
    {
        $schema->arguments()->delete();

        foreach ($arguments as $arg) {
            $arg['schema_id'] = $schema->id;
            $argument = Argument::create($arg);

            if (isset($arg['options']) && is_array($arg['options'])) {
                foreach ($arg['options'] as $option) {
                    $option['argument_id'] = $argument->id;
                    Option::create($option);
                }
            }
        }
    }
}
