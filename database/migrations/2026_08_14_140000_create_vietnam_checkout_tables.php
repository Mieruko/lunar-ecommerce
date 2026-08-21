<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->unsignedBigInteger('fee_vnd')->default(0);
            $table->unsignedBigInteger('free_shipping_threshold_vnd')->default(5_000_000);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vn_provinces', function (Blueprint $table) {
            $table->char('code', 2)->primary();
            $table->string('name');
            $table->string('full_name');
            $table->foreignId('shipping_zone_id')
                ->nullable()
                ->constrained('shipping_zones')
                ->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['shipping_zone_id', 'sort_order']);
        });

        Schema::create('vn_wards', function (Blueprint $table) {
            $table->char('code', 5)->primary();
            $table->char('province_code', 2);
            $table->string('name');
            $table->string('full_name');
            $table->string('unit_type', 30)->nullable();
            $table->foreignId('shipping_zone_id')
                ->nullable()
                ->constrained('shipping_zones')
                ->nullOnDelete();
            $table->timestamps();

            $table->foreign('province_code')
                ->references('code')
                ->on('vn_provinces')
                ->cascadeOnDelete();

            $table->index(['province_code', 'name']);
            $table->index('shipping_zone_id');
        });

        Schema::table('order_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('order_addresses', 'province_code')) {
                $table->char('province_code', 2)->nullable()->after('province');
            }

            if (! Schema::hasColumn('order_addresses', 'ward_code')) {
                $table->char('ward_code', 5)->nullable()->after('ward');
            }

            if (! Schema::hasColumn('order_addresses', 'shipping_zone_code')) {
                $table->string('shipping_zone_code', 40)->nullable()->after('province_code');
            }
        });

        Schema::table('addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('addresses', 'province_code')) {
                $table->char('province_code', 2)->nullable()->after('province');
            }

            if (! Schema::hasColumn('addresses', 'ward_code')) {
                $table->char('ward_code', 5)->nullable()->after('ward');
            }
        });

        /*
         * Nếu trước đó đã thử patch GHN, dọn các cột GHN để schema quay về
         * nguồn dữ liệu nội bộ. Các lệnh được kiểm tra bằng hasColumn nên
         * migration vẫn chạy được trên project chưa từng cài patch GHN.
         */
        foreach (['ghn_ward_code', 'ghn_district_id', 'ghn_province_id'] as $column) {
            if (Schema::hasColumn('order_addresses', $column)) {
                Schema::table('order_addresses', fn (Blueprint $table) => $table->dropColumn($column));
            }

            if (Schema::hasColumn('addresses', $column)) {
                Schema::table('addresses', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }

    public function down(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            foreach (['province_code', 'ward_code', 'shipping_zone_code'] as $column) {
                if (Schema::hasColumn('order_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('addresses', function (Blueprint $table) {
            foreach (['province_code', 'ward_code'] as $column) {
                if (Schema::hasColumn('addresses', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('vn_wards');
        Schema::dropIfExists('vn_provinces');
        Schema::dropIfExists('shipping_zones');
    }
};
