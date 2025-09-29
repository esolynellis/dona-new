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

        if (!Schema::hasTable('commission_orders')) {
            Schema::create('commission_orders', function (Blueprint $table) {
                $table->comment('分销的订单');
                $table->id()->comment('ID');
                $table->bigInteger('customer_id')->comment('系统客户ID');
                $table->bigInteger('commission_user_id')->comment('分佣用户ID');
                $table->bigInteger('order_id')->default(0)->comment('订单ID');
                $table->integer('status')->default(1)->comment('状态：1.待结算，2.已结算');
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
