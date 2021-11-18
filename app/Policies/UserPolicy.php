<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->can('role.index');
    }

    public function show(User $user)
    {
        return $user->can('user.show');
    }

    public function update(User $user)
    {
        return $user->can('user.update');
    }

    public function delete(User $user)
    {
        return $user->can('user.delete');
    }
}
