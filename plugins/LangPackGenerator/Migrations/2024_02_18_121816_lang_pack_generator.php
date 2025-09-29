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
        if (!Schema::hasTable('lang_pack_generator_replace')) {
            Schema::create('lang_pack_generator_replace', function (Blueprint $table) {
                $table->id();
                $table->string('lang')->comment('语言');
                $table->string('search')->comment('搜索字符串');
                $table->string('source_text')->comment('源字符');
                $table->string('to_text')->comment('替换字符');
                $table->string('file')->comment('所属文件');
                $table->integer('line')->nullable()->default(0)->comment('所属行');
                $table->timestamps();
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
