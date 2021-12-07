<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServerPolicy
{
    use HandlesAuthorization;

    public function show(User $user)
    {
        return $user->can('server.show');
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

    public function sync(User $user)
    {
        return $user->can('server.sync');
    }
}
