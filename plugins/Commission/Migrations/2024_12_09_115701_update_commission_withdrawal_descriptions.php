<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('commission_withdrawal_group_item_descriptions')) {
            Schema::create('commission_withdrawal_group_item_descriptions', function (Blueprint $table) {
                $table->comment('多语言');
                $table->id()->comment('ID');
                $table->string('content')->comment('内容');
                $table->string('locale')->comment('语言');
                $table->integer('commission_withdrawal_group_item_id')->comment('配置ID');
                $table->timestamps();
            });
        }
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
};
