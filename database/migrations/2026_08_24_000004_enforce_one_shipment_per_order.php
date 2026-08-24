<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shipments')
            ->where('carrier', 'LUNAR Fulfillment')
            ->where('status', 'pending')
            ->where('tracking_number', 'like', 'LJ-%')
            ->whereNull('shipped_at')
            ->whereNull('delivered_at')
            ->delete();

        Schema::table('shipments', function (Blueprint $table): void {
            $table->unique('order_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropUnique(['order_id']);
        });
    }
};
