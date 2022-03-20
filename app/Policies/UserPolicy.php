<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function show(User $user): bool
    {
        return $user->can('user.show');
    }

    public function update(User $user): bool
    {
        return $user->can('user.update');
    }

    public function delete(User $user): bool
    {
        return $user->can('user.delete');
    }

    public function restore(User $user): bool
    {
        return $user->can('user.restore');
    }
}
