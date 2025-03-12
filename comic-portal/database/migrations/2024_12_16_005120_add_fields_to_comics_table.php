<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('comics', function (Blueprint $table) {
            // Only add price column since featured and status were added in previous migration
            $table->decimal('price', 8, 2)->default(0.00);
        });
    }

    public function down()
    {
        Schema::table('comics', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};