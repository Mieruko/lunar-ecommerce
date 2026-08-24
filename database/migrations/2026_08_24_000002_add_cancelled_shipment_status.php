<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE shipments MODIFY status ENUM('pending', 'packed', 'shipped', 'delivered', 'failed', 'returned', 'cancelled') NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('shipments', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'packed', 'shipped', 'delivered', 'failed', 'returned', 'cancelled'])
                ->default('pending')
                ->change();
        });
    }

    public function down(): void
    {
        DB::table('shipments')->where('status', 'cancelled')->update(['status' => 'failed']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE shipments MODIFY status ENUM('pending', 'packed', 'shipped', 'delivered', 'failed', 'returned') NOT NULL DEFAULT 'pending'");

            return;
        }

        Schema::table('shipments', function (Blueprint $table): void {
            $table->enum('status', ['pending', 'packed', 'shipped', 'delivered', 'failed', 'returned'])
                ->default('pending')
                ->change();
        });
    }
};
