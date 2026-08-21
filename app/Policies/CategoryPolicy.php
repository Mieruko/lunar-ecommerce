<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\Concerns\ChecksAdminPermission;

class CategoryPolicy
{
    use ChecksAdminPermission;

    public function viewAny(User $user): bool { return $this->allows($user, 'catalog.view'); }
    public function view(User $user, Category $category): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allows($user, 'catalog.create'); }
    public function update(User $user, Category $category): bool { return $this->allows($user, 'catalog.update'); }
    public function delete(User $user, Category $category): bool { return $this->allows($user, 'catalog.delete'); }
    public function deleteAny(User $user): bool { return $this->allows($user, 'catalog.delete'); }
    public function restore(User $user, Category $category): bool { return $this->allows($user, 'catalog.update'); }
    public function restoreAny(User $user): bool { return $this->allows($user, 'catalog.update'); }
    public function forceDelete(User $user, Category $category): bool { return false; }
    public function forceDeleteAny(User $user): bool { return false; }
}
