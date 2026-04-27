<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'featured', 'rating', 'reviews_count']);
            $table->string('size')->nullable()->after('brand');
        });

        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('size');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->boolean('featured')->default(false);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
        });
    }
};
