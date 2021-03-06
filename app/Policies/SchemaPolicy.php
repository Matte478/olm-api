<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SchemaPolicy
{
    use HandlesAuthorization;

    public function show(User $user): bool
    {
        return $user->canAny(['schema.show', 'user_experiment.create']);
    }

    public function create(User $user): bool
    {
        return $user->can('schema.create');
    }

    public function update(User $user): bool
    {
        return $user->can('schema.update');
    }

    public function delete(User $user): bool
    {
        return $user->can('schema.delete');
    }

    public function restore(User $user): bool
    {
        return $user->can('schema.restore');
    }
}
