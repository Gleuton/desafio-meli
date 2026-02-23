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
        Schema::create('items', static function (Blueprint $table) {
            $table->id();

            $table->string('meli_id')->unique();
            $table->string('title')->nullable();
            $table->string('category_id')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency_id')->nullable();
            $table->string('condition')->nullable();
            $table->string('listing_type_id')->nullable();
            $table->string('permalink')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('seller_id')->nullable();

            $table->string('status')->default('pending');
            $table->json('raw_payload')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->text('failed_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
