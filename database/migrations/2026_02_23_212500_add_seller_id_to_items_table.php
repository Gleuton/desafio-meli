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
        Schema::table('items', static function (Blueprint $table) {
            $table->string('seller_id')->nullable()->after('meli_id');
            $table->index('seller_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', static function (Blueprint $table) {
            $table->dropIndex(['seller_id']);
            $table->dropColumn('seller_id');
        });
    }
};
