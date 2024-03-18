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
        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('item_name', 'name');
            $table->renameColumn('item_description', 'description');
            $table->renameColumn('item_quantity', 'quantity');
            $table->renameColumn('item_image', 'image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->renameColumn('name', 'item_name');
            $table->renameColumn('description', 'item_description');
            $table->renameColumn('quantity', 'item_quantity');
            $table->renameColumn('image', 'item_image');
        });
    }
};
