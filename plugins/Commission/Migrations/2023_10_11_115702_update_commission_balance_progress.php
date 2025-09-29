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
        if (!Schema::hasColumn('commission_users', 'balance_progress')) {
            Schema::table('commission_users', function (Blueprint $table) {
                $table->double('balance_progress')->comment('等待结算的金额')->default(0)->after('balance');
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
