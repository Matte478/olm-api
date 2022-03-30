<?php

namespace App\GraphQL\Mutations;

use App\Models\Experiment;
use App\Models\Schema;
use App\Models\Software;
use App\Models\UserExperiment;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class RunUserExperiment
{
    /**
     * Return a value for the field.
     *
     * @param  @param  null  $root Always null, since this field has no parent.
     * @param  array<string, mixed>  $args The field arguments passed by the client.
     * @param  \Nuwave\Lighthouse\Support\Contracts\GraphQLContext  $context Shared between all fields.
     * @param  \GraphQL\Type\Definition\ResolveInfo  $resolveInfo Metadata for advanced query resolution.
     * @return mixed
     */
    public function __invoke($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $experiment = Experiment::findOrFail($args['experiment_id']);
        $software = Software::findOrFail($args['software_id']);
        $schema = isset($args['schema_id']) ? Schema::findOrFail($args['schema_id']) : null;
        $userExperiment = isset($args['user_experiment_id']) ? UserExperiment::findOrFail($args['user_experiment_id']) : null;

        return app(\App\Actions\RunUserExperiment::class)->execute(
            $experiment, $args['input'][0]['script_name'], $args['input'][0]['input'],
            $software, $schema, $userExperiment
        );
    }
}
