<?php

namespace App\GraphQL\Mutations;

use App\Exceptions\BusinessLogicException;
use App\Models\Server;
use GraphQL\Type\Definition\ResolveInfo;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SyncAllServers
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
        $args['trashed'] = $args['trashed'] ?? 'without';

        switch ($args['trashed']) {
            case 'with':
                $servers = Server::withTrashed()->get();
                break;
            case 'only':
                $servers = Server::onlyTrashed()->get();
                break;
            case 'without':
            default:
                $servers = Server::all();
        }

        $errors = [];
        foreach ($servers as $server) {
            if($server->trashed()) continue;
            try {
                app(\App\Actions\SyncServer::class)->execute($server);
            } catch (\Throwable $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if(count($errors)) {
            throw new BusinessLogicException(implode(" | ",$errors));
        }

        return $servers;
    }
}
