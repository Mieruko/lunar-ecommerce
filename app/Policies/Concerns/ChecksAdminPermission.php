<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksAdminPermission
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdministrator() ? true : null;
    }

    protected function allows(User $user, string $permission): bool
    {
        return $user->status === 'active' && $user->hasPermission($permission);
    }
}
