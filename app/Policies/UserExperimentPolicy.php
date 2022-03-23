<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UserExperiment;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserExperimentPolicy
{
    use HandlesAuthorization;

    public function show(User $user, array | UserExperiment $injected): bool
    {
        if($injected instanceof UserExperiment)
            return $user->can('user_experiment.show_all') ||
                ($user->can('user_experiment.show_own') && $injected->user_id === $user->id);

        if(!isset($injected['onlyMine']) || !$injected['onlyMine'])
            return $user->can('user_experiment.show_all');

        return $user->canAny(['user_experiment.show_own', 'user_experiment.show_all']);
    }

    public function create(User $user): bool
    {
        return $user->can('user_experiment.create');
    }

    public function update(User $user, UserExperiment $userExperiment): bool
    {
        return $user->can('user_experiment.update_all') ||
            ($user->can('user_experiment.update_own') && $userExperiment->user_id === $user->id);
    }

    public function delete(User $user, UserExperiment $userExperiment): bool
    {
        return $user->can('user_experiment.delete_all') ||
            ($user->can('user_experiment.delete_own') && $userExperiment->user_id === $user->id);
    }

    public function restore(User $user, UserExperiment $userExperiment): bool
    {
        return $user->can('user_experiment.restore_all') ||
            ($user->can('user_experiment.restore_own') && $userExperiment->user_id === $user->id);
    }
}
