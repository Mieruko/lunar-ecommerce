<?php

namespace App\Policies;

use App\Models\Inventory;
use App\Models\User;
use App\Policies\Concerns\ChecksAdminPermission;

class InventoryPolicy
{
    use ChecksAdminPermission;
    public function viewAny(User $user): bool { return $this->allows($user, 'inventory.view'); }
    public function view(User $user, Inventory $inventory): bool { return $this->viewAny($user); }
    public function update(User $user, Inventory $inventory): bool { return $this->allows($user, 'inventory.adjust'); }
    public function create(User $user): bool { return false; }
    public function delete(User $user, Inventory $inventory): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
}
