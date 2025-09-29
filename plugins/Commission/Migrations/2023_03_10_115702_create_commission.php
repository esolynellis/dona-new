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
        if (!Schema::hasTable('commission_users')) {
            Schema::create('commission_users', function (Blueprint $table) {
                $table->comment('分佣用户');
                $table->id()->comment('ID');
                $table->integer('customer_id')->comment('对应系统客户ID');
                $table->string('code')->comment('分销码');
                $table->integer('balance')->default(0)->comment('佣金余额');
                $table->integer('total_amount')->default(0)->unsigned()->comment('总佣金');
                $table->integer('status')->default(1)->comment('状态：1.待审核，2.正常，3.冻结');
                $table->integer('rate_1')->default(-1)->comment('一级分佣比例,-1表示未单独设置,执行统一的配置');
                $table->integer('rate_2')->default(-1)->comment('二级分佣比例,-1表示未单独设置,执行统一的配置');
                $table->integer('rate_3')->default(-1)->comment('三级分佣比例,-1表示未单独设置,执行统一的配置');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('commission_customers')) {
            Schema::create('commission_customers', function (Blueprint $table) {
                $table->comment('分佣用户发展的客户');
                $table->id()->comment('ID');
                $table->integer('commission_user_id')->comment('分佣用户ID');
                $table->integer('customer_id')->comment('系统客户ID');
                $table->timestamps();
            });
        }
        if (!Schema::hasTable('commission_amount_logs')) {
            Schema::create('commission_amount_logs', function (Blueprint $table) {
                $table->comment('佣金日志订单');
                $table->id()->comment('ID');
                $table->bigInteger('commission_user_id')->comment('分佣用户ID');
                $table->integer('customer_id')->comment('分佣用户客户ID');
                $table->integer('order_id')->default(0)->comment('订单ID');
                $table->string('action')->default('order')->comment('事件：order:订单佣金，refund:退款，close:提现');
                $table->integer('level')->default(0)->comment('返佣等级');
                $table->integer('rate')->default(0)->comment('分佣比例');
                $table->integer('base_amount')->default(0)->unsigned()->comment('基于分佣的金额');
                $table->integer('amount')->default(0)->comment('分佣金额');
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
