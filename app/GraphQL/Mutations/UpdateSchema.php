<?php

namespace App\GraphQL\Mutations;

use App\Actions\SyncSchemaArguments;
use App\Models\Schema;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class UpdateSchema
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
        $schema = Schema::findOrFail($args['id']);

        $schema->update($args);

        if(isset($args['schema']) && $args['schema']->isValid()) {
            $schema->addMedia($args['schema'])->toMediaCollection('schema');
        }

        if(isset($args['preview']) && $args['preview']->isValid()) {
            $schema->addMedia($args['preview'])->toMediaCollection('preview');
        }

        app(SyncSchemaArguments::class)->execute($schema, $args['arguments'] ?? []);

        return $schema;
    }
}
