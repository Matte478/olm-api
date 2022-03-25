<?php

namespace App\GraphQL\Queries;

use App\Actions\SyncUserExperiment;
use App\Models\Reservation;
use App\Models\UserExperiment;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UserExperimentCurrent
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
        $reservation = Reservation::current()->first();

        if(!$reservation) return null;

        $unfinished = UserExperiment::unfinished()->filterDevice($reservation->device_id)->get();

        foreach ($unfinished as $userExperiment) {
            app(SyncUserExperiment::class)->execute($userExperiment);
        }

        return UserExperiment::unfinished()->filterDevice($reservation->device_id)->first();
    }
}
