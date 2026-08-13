<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_cents');   // Integer
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable();
            $table->timestamps();
            $table->softDeletes();  // added a nullable deleted_at column

            $table->index(['is_active', 'category_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
