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
        if (Schema::hasTable('lang_pack_generator')) {
            Schema::table('lang_pack_generator', function (Blueprint $table) {
                // v1.3.2
                $table->string('custom_name')->comment('自定义名称')->nullable() ;
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
