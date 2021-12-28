<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\BusinessLogicException;
use App\Models\Reservation;
use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Validation\ValidationException;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UpdateReservation
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
        $reservation = Reservation::findOrFail($args['id']);

        return app(\App\Actions\UpdateReservation::class)->execute($reservation, $args);
    }
}
