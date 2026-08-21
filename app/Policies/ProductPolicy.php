<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\Concerns\ChecksAdminPermission;

class ProductPolicy
{
    use ChecksAdminPermission;

    public function viewAny(User $user): bool { return $this->allows($user, 'catalog.view'); }
    public function view(User $user, Product $product): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allows($user, 'catalog.create'); }
    public function update(User $user, Product $product): bool { return $this->allows($user, 'catalog.update'); }
    public function delete(User $user, Product $product): bool { return $this->allows($user, 'catalog.delete'); }
    public function deleteAny(User $user): bool { return $this->allows($user, 'catalog.delete'); }
    public function restore(User $user, Product $product): bool { return $this->allows($user, 'catalog.update'); }
    public function restoreAny(User $user): bool { return $this->allows($user, 'catalog.update'); }
    public function forceDelete(User $user, Product $product): bool { return false; }
    public function forceDeleteAny(User $user): bool { return false; }
}
