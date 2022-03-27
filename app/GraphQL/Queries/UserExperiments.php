<?php

namespace App\GraphQL\Queries;

use App\Actions\SyncUserExperiment;
use App\Models\UserExperiment;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UserExperiments
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
        $unfinished = UserExperiment::executed(false)->unfinished(false)->get();

        foreach ($unfinished as $userExperiment) {
            app(SyncUserExperiment::class)->execute($userExperiment);
        }

        $query = UserExperiment::query();

        if($args['onlyMine'])
            $query->where('user_id', auth()->user()->id);

        return $query;
    }
}
