<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\BusinessLogicException;
use App\Models\Reservation;
use Carbon\Carbon;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class CreateReservation
{
    /**
     * Return a value for the field.
     *
     * @param  @param  null  $root Always null, since this field has no parent.
     * @param array<string, mixed> $args The field arguments passed by the client.
     * @param \Nuwave\Lighthouse\Support\Contracts\GraphQLContext $context Shared between all fields.
     * @param \GraphQL\Type\Definition\ResolveInfo $resolveInfo Metadata for advanced query resolution.
     * @return mixed
     * @throws BusinessLogicException
     */
    public function __invoke($root, array $args, GraphQLContext $context, ResolveInfo $resolveInfo)
    {
        $args['user_id'] = auth()->user()->id;

        return app(\App\Actions\CreateReservation::class)->execute($args);
    }
}
