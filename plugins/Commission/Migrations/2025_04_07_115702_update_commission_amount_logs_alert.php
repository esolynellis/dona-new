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


        if (!Schema::hasColumn('commission_amount_logs', 'is_notify')) {
            Schema::table('commission_amount_logs', function (Blueprint $table) {
                $table->integer('is_notify')->default(1)->comment('是否已通知');
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
