<?php

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
        if (!Schema::hasColumn('customers', 'ip')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('ip')->comment('ip')->nullable(true);
            });
        }
        if (!Schema::hasColumn('customers', 'balance')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->decimal('balance',15,2)->comment('余额')->default(0);
            });
        }
        if (!Schema::hasColumn('customers', 'can_apply_to_bank')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->boolean('can_apply_to_bank')->comment('提现申请按钮')->default(0);
            });
        }
        if (!Schema::hasColumn('customers', 'login_time')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dateTime('login_time')->comment('登录时间')->nullable(true);
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
