<?php

/**
 *
 * @author     村长+ <178277164@qq.com>
 */

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
        if (!Schema::hasTable('commission_withdrawal_group')) {
            Schema::create('commission_withdrawal_group', function (Blueprint $table) {
                $table->comment('提现方式');
                $table->id()->comment('ID');
                $table->string('name')->comment('组名');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('commission_withdrawal_group_item')) {
            Schema::create('commission_withdrawal_group_item', function (Blueprint $table) {
                $table->comment('提现内容');
                $table->id()->comment('ID');
                $table->integer('group_id')->comment('组id');
                $table->string('name')->comment('组名');
                $table->integer('show_sort')->comment('排序');
                $table->integer('type')->comment('类型')->default(1);
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
