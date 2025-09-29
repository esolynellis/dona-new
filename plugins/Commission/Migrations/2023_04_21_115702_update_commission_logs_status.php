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
        if (!Schema::hasColumn('commission_amount_logs', 'status')) {
            Schema::table('commission_amount_logs', function (Blueprint $table) {
                $table->integer('status')->comment('状态：1正常,2.已退款,3.已打款,4.已拒绝打款，5.待结算')->default(1)->after('amount');
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
