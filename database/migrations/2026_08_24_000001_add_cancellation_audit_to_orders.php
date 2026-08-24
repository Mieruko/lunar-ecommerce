<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('cancellation_reason', 500)->nullable()->after('note');
            $table->timestamp('cancelled_at')->nullable()->after('placed_at');
            $table->timestamp('stock_reverted_at')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['cancellation_reason', 'cancelled_at', 'stock_reverted_at']);
        });
    }
};
