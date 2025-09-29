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


        if (!Schema::hasColumn('commission_amount_logs', 'apply_data')) {
            Schema::table('commission_amount_logs', function (Blueprint $table) {
                $table->text('apply_data')->comment('申请的打款帐户信息')->nullable()->after('status');
            });
        }

        if (!Schema::hasColumn('commission_amount_logs', 'audit_note')) {
            Schema::table('commission_amount_logs', function (Blueprint $table) {
                $table->text('audit_note')->comment('审核申请的备注')->nullable()->after('apply_data');
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
