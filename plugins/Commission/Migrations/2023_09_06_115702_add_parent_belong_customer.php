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
        if (!Schema::hasColumn('customers', 'parent_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->integer('parent_id')->unsigned()->comment('归属的代理id')->default(0);
            });
        }
        if (!Schema::hasColumn('customers', 'commission_code')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('commission_code')->comment('推广码')->nullable()->default(null);
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
