<?php

namespace App\Policies;

use App\Models\Microcycle;
use App\Models\User;

class MicrocyclePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Microcycle $microcycle): bool
    {
        return $user->id == $microcycle->mesocycle->macrocycle->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Microcycle $microcycle): bool
    {
        return $user->id == $microcycle->mesocycle->macrocycle->user_id;
    }

    public function delete(User $user, Microcycle $microcycle): bool
    {
        return $user->id == $microcycle->mesocycle->macrocycle->user_id;
    }
}
