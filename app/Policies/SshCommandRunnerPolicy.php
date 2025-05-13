<?php

namespace App\Policies;

use App\Models\User;

class SshCommandRunnerPolicy
{
    /**
     * Determine whether the user can view the SSH command runner page.
     */
    public function view(User $user): bool
    {
        return true;
    }
}
