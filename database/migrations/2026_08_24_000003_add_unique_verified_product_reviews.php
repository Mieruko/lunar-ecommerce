<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('reviews')
            ->select('user_id', 'product_id', DB::raw('MAX(id) as keep_id'))
            ->groupBy('user_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            DB::table('reviews')
                ->where('user_id', $duplicate->user_id)
                ->where('product_id', $duplicate->product_id)
                ->where('id', '<>', $duplicate->keep_id)
                ->delete();
        }

        Schema::table('reviews', function (Blueprint $table): void {
            $table->unique(['user_id', 'product_id'], 'reviews_user_product_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropUnique('reviews_user_product_unique');
        });
    }
};
