<?php

namespace App\GraphQL\Mutations;

use GraphQL\Type\Definition\ResolveInfo;
use Illuminate\Support\Facades\Auth;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SocialLogin extends \Joselfonseca\LighthouseGraphQLPassport\GraphQL\Mutations\Register
{

    public function resolve($rootValue, array $args, GraphQLContext $context = null, ResolveInfo $resolveInfo)
    {
        $credentials = $this->buildCredentials($args, 'social_grant');
        $response = $this->makeRequest($credentials);
        $model = $this->makeAuthModelInstance();
        $user = $model->where('id', Auth::user()->id)->firstOrFail();

        if($user->getRoleNames()->count() === 0)
            $user->assignRole('Student');

        $response['user'] = $user;

        return $response;
    }
}
