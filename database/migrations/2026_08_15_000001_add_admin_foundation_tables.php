<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('roles', 'is_staff')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_staff')->default(false)->after('slug');
            });
        }

        if (! Schema::hasColumn('roles', 'is_system')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('is_staff');
            });
        }

        // Preserve the legacy `admin` role from lunar_ecommerce.sql while
        // keeping customer roles non-staff by default.
        DB::table('roles')
            ->whereIn('slug', [
                'admin',
                'super-admin',
                'product-manager',
                'inventory-clerk',
                'order-manager',
                'customer-service',
                'marketing',
                'accountant',
            ])
            ->update(['is_staff' => true, 'is_system' => true]);

        DB::table('roles')
            ->where('slug', 'customer')
            ->update(['is_staff' => false]);

        if (! Schema::hasTable('admin_activity_logs')) {
            Schema::create('admin_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 120);
                $table->nullableMorphs('subject');
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->uuid('request_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['action', 'created_at']);
            });
        }

        if (! Schema::hasTable('order_notes')) {
            Schema::create('order_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained()->cascadeOnDelete();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('body');
                $table->boolean('is_internal')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('inventory_serial_numbers')) {
            Schema::create('inventory_serial_numbers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
                $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
                $table->string('serial_number')->unique();
                $table->enum('status', ['in_stock', 'reserved', 'sold', 'returned', 'void'])->default('in_stock');
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['product_variant_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_serial_numbers');
        Schema::dropIfExists('order_notes');
        Schema::dropIfExists('admin_activity_logs');

        if (Schema::hasColumn('roles', 'is_system')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }

        if (Schema::hasColumn('roles', 'is_staff')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropColumn('is_staff');
            });
        }
    }
};
