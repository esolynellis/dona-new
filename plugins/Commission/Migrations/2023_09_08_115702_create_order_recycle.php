<?php

/**

 *
 * @author     村长+ <178277164@qq.com>
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('order_recycle')) {
            Schema::create('order_recycle', function (Blueprint $table) {
                $table->comment('回收订单');
                $table->id()->comment('ID');
                $table->integer('customer_id')->comment('对应系统客户ID');
                $table->string('order_id')->comment('订单号');
                $table->integer('amount')->default(0)->comment('余额');
                $table->integer('total_amount')->default(0)->unsigned()->comment('总金额');
                $table->integer('status')->default(0)->comment('确认状态');
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
        //Schema::dropIfExists('commission_users');
        //Schema::dropIfExists('commission_customers');
        //Schema::dropIfExists('commission_amount_logs');
    }
};
