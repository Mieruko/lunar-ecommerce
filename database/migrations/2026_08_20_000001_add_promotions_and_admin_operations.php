<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coupons')) {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 80)->unique();
            $table->string('name');
            $table->enum('discount_type', ['percent', 'fixed']);
            $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('minimum_order_amount')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        } else {
            Schema::table('coupons', function (Blueprint $table) {
                if (! Schema::hasColumn('coupons', 'name')) $table->string('name')->nullable()->after('code');
                if (! Schema::hasColumn('coupons', 'per_customer_limit')) $table->unsignedInteger('per_customer_limit')->nullable()->after('usage_limit');
                if (! Schema::hasColumn('coupons', 'usage_count')) $table->unsignedInteger('usage_count')->default(0)->after('per_customer_limit');
                if (! Schema::hasColumn('coupons', 'updated_at')) $table->timestamp('updated_at')->nullable()->after('created_at');
            });
            if (Schema::hasColumn('coupons', 'per_user_limit')) DB::table('coupons')->update(['per_customer_limit' => DB::raw('per_user_limit')]);
            DB::table('coupons')->whereNull('name')->update(['name' => DB::raw("concat('Mã ưu đãi ', code)")]);
        }

        if (! Schema::hasTable('coupon_redemptions')) Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_email')->nullable();
            $table->unsignedBigInteger('discount_amount');
            $table->timestamp('redeemed_at')->useCurrent();
            $table->unique('order_id');
            $table->index(['coupon_id', 'customer_email']);
        });

        if (! Schema::hasTable('refunds')) Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->string('reason', 500);
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'coupon_id')) $table->foreignId('coupon_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            if (! Schema::hasColumn('orders', 'coupon_code')) $table->string('coupon_code', 80)->nullable()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'coupon_id')) $table->dropConstrainedForeignId('coupon_id');
            if (Schema::hasColumn('orders', 'coupon_code')) $table->dropColumn('coupon_code');
        });
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('coupon_redemptions');
    }
};
