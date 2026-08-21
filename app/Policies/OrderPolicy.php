<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Policies\Concerns\ChecksAdminPermission;

class OrderPolicy
{
    use ChecksAdminPermission;

    public function viewAny(User $user): bool { return $this->allows($user, 'orders.view'); }
    public function view(User $user, Order $order): bool { return $this->viewAny($user); }
    public function update(User $user, Order $order): bool { return $this->allows($user, 'orders.update_status'); }
    public function create(User $user): bool { return false; }
    public function delete(User $user, Order $order): bool { return false; }
    public function deleteAny(User $user): bool { return false; }
}
