<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;
use App\Policies\Concerns\ChecksAdminPermission;

class BrandPolicy
{
    use ChecksAdminPermission;

    public function viewAny(User $user): bool { return $this->allows($user, 'catalog.view'); }
    public function view(User $user, Brand $brand): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allows($user, 'catalog.create'); }
    public function update(User $user, Brand $brand): bool { return $this->allows($user, 'catalog.update'); }
    public function delete(User $user, Brand $brand): bool { return $this->allows($user, 'catalog.delete'); }
    public function deleteAny(User $user): bool { return $this->allows($user, 'catalog.delete'); }
}
