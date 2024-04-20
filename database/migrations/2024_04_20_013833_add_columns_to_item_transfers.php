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
        Schema::table('item_transfers', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->string('status')->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('item_transfers', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
            $table->dropColumn('approved_by');
            $table->dropColumn('approved_at');
            $table->dropColumn('status');
        });
    }
};
