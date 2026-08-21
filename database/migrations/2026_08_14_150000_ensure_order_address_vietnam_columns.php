<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix databases created from the older schema dump / partial import where
     * order_addresses was created before the Vietnam checkout columns existed.
     */
    public function up(): void
    {
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
    }

    public function down(): void
    {
        Schema::table('order_addresses', function (Blueprint $table) {
            foreach (['shipping_zone_code', 'province_code', 'ward_code'] as $column) {
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
    }
};
