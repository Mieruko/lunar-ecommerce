<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\AdminActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_render_every_current_admin_resource(): void
    {
        $permission = Permission::create(['name' => 'Admin Access', 'slug' => 'admin.access']);
        $role = Role::create(['name' => 'Super Admin', 'slug' => 'super-admin', 'is_staff' => true, 'is_system' => true]);
        $role->permissions()->attach($permission);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $user->roles()->attach($role);

        foreach ([
            '/admin', '/admin/categories', '/admin/brands', '/admin/products', '/admin/collections', '/admin/materials', '/admin/gemstones',
            '/admin/inventories', '/admin/warehouses', '/admin/inventory-transactions', '/admin/serial-numbers',
            '/admin/orders', '/admin/shipments', '/admin/payments', '/admin/shipping-zones', '/admin/vietnam-provinces', '/admin/vietnam-wards', '/admin/coupons',
            '/admin/customers', '/admin/reviews', '/admin/returns/return-requests', '/admin/warranties', '/admin/warranty-claims',
            '/admin/staff', '/admin/roles', '/admin/activity-logs', '/admin/settings/store-settings', '/admin/sales-report',
        ] as $url) {
            $response = $this->actingAs($user)->get($url);
            if ($response->exception !== null) {
                throw $response->exception;
            }
            $this->assertSame(200, $response->getStatusCode(), "Admin route failed: {$url}");
            if ($url === '/admin/shipments') {
                $response->assertSee('Đơn hàng chờ tạo vận đơn');
                $response->assertSee('Không có đơn chờ tạo vận đơn');
            }
        }
    }

    public function test_activity_log_only_identifies_staff_as_admin_actor(): void
    {
        $buyer = User::create([
            'name' => 'Buyer',
            'email' => 'buyer@example.test',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $this->actingAs($buyer);
        app(AdminActivityLogger::class)->log('payment.mark_paid');
        $this->assertNull(AdminActivityLog::latest('id')->firstOrFail()->actor_id);

        $role = Role::create(['name' => 'Staff', 'slug' => 'staff', 'is_staff' => true, 'is_system' => false]);
        $staff = User::create([
            'name' => 'Staff',
            'email' => 'staff@example.test',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);
        $staff->roles()->attach($role);
        $this->actingAs($staff);
        app(AdminActivityLogger::class)->log('shipment.status_changed');
        $this->assertSame($staff->id, AdminActivityLog::latest('id')->firstOrFail()->actor_id);
    }
}
