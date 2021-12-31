<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerPolicy
{
    use HandlesAuthorization;

    public function show(User $user)
    {
        return $user->canAny(['server.show', 'reservation.show']);
    }

    public function create(User $user)
    {
        return $user->can('server.create');
    }

    public function update(User $user)
    {
        return $user->can('server.update');
    }

    public function delete(User $user)
    {
        return $user->can('server.delete');
    }

    public function restore(User $user)
    {
        return $user->can('server.restore');
    }

    public function sync(User $user)
    {
        return $user->can('server.sync');
    }
}
