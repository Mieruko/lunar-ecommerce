<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'pending_confirmation', 'confirmed', 'processing', 'preparing', 'shipped', 'shipping', 'completed', 'cancelled', 'returned') NOT NULL DEFAULT 'pending'");
        }
        DB::table('orders')->where('status', 'pending')->update(['status' => 'pending_confirmation']);
        DB::table('orders')->where('status', 'processing')->update(['status' => 'preparing']);
        DB::table('orders')->where('status', 'shipped')->update(['status' => 'shipping']);
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending_confirmation', 'confirmed', 'preparing', 'shipping', 'completed', 'cancelled', 'returned') NOT NULL DEFAULT 'pending_confirmation'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'pending_confirmation', 'confirmed', 'processing', 'preparing', 'shipped', 'shipping', 'completed', 'cancelled', 'returned') NOT NULL DEFAULT 'pending_confirmation'");
        }
        DB::table('orders')->where('status', 'pending_confirmation')->update(['status' => 'pending']);
        DB::table('orders')->where('status', 'preparing')->update(['status' => 'processing']);
        DB::table('orders')->where('status', 'shipping')->update(['status' => 'shipped']);
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending', 'confirmed', 'processing', 'shipped', 'completed', 'cancelled', 'returned') NOT NULL DEFAULT 'pending'");
        }
    }
};
