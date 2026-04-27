<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            $table->boolean('free_shipping')->default(true)->after('sort_order');
        });

        // Mark all existing kits as free shipping
        DB::table('kits')->update(['free_shipping' => true]);
    }

    public function down(): void
    {
        Schema::table('kits', function (Blueprint $table) {
            $table->dropColumn('free_shipping');
        });
    }
};
