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
        if (!Schema::hasTable('balance_logs')) {
            Schema::create('balance_logs', function (Blueprint $table) {
                $table->comment('提现申请');
                $table->id()->comment('ID');
                $table->integer('customer_id')->comment('对应系统客户ID');
                $table->string('customer_account')->comment('对应系统客户Account');
                $table->string('bank_user_name')->comment('银行卡开户名');
                $table->string('bank_name')->comment('银行名');
                $table->string('bank_code')->comment('银行卡');
                $table->decimal('amount')->default(0)->comment('金额');
                $table->string('note')->nullable(true)->comment('备注');
                $table->string('status')->nullable(false)->comment('状态');
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
