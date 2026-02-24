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
            $table->string('status')->nullable();
            $table->text('failed_reason')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('created')->nullable();
            $table->timestamp('updated')->nullable();

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
