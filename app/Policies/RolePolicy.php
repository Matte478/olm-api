<?php

namespace App\Policies;

use App\Models\Spatie\Permission\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function index(User $user)
    {
        return $user->can('role.index');
    }

    public function show(User $user)
    {
        return $user->can('role.show');
    }

    public function update(User $user)
    {
        return $user->can('role.update');
    }

    public function delete(User $user)
    {
        return $user->can('role.delete');
    }
}
