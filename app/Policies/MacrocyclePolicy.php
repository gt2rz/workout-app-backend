<?php

namespace App\Policies;

use App\Models\Macrocycle;
use App\Models\User;

class MacrocyclePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Macrocycle $macrocycle): bool
    {
        return $user->id == $macrocycle->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Macrocycle $macrocycle): bool
    {
        return $user->id == $macrocycle->user_id;
    }

    public function delete(User $user, Macrocycle $macrocycle): bool
    {
        return $user->id == $macrocycle->user_id;
    }
}
