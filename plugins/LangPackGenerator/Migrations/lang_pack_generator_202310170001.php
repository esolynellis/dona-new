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

        if (!Schema::hasTable('lang_pack_generator')) {
            Schema::create('lang_pack_generator', function (Blueprint $table) {
                $table->id();
                $table->string('from_name')->comment('来源语言包名称');
                $table->string('from_code')->comment('来源语言包编码');
                $table->string('to_name')->comment('目标语言包名称');
                $table->string('to_code')->comment('目标语言包编码');
                $table->integer('type')->nullable()->default(0)->comment('语言包类型');
                $table->string('plugin_code')->nullable()->comment('插件代码');
                $table->string('running')->nullable()->comment('当前运行进程');
                $table->string('thread_id')->nullable()->comment('线程ID');
                $table->string('run_task_number')->nullable()->default(0)->comment('运行完成任务');
                $table->integer('task_number')->nullable()->default(0)->comment('总任务数量');
                $table->longText('errors')->nullable()->comment('执行失败统计');
                $table->longText('success')->nullable()->comment('成功数据统计');
                $table->longText('files')->nullable()->comment('文件列表');
                $table->longText('result')->nullable()->comment('生成结果');
                $table->integer('status')->nullable()->default(0)
                    ->comment('状态:0=未生成,1=生成中,2=已完成,3=暂停,4=运行出错');
                $table->integer('start_time')->nullable()->default(0)->comment('开始运行时间');
                $table->integer('end_time')->nullable()->default(0)->comment('结束运行时间');
                // v1.3.2
                $table->string('custom_name')->comment('自定义名称')->nullable() ;
                $table->timestamps();
            });
        }


        if (!Schema::hasTable('lang_pack_generator_logs')) {
            Schema::create('lang_pack_generator_logs', function (Blueprint $table) {
                $table->id();
                $table->integer('task_id')->default(0)->comment('任务ID');
                $table->string('thread_id')->nullable()->comment('线程ID');
                $table->text('file')->nullable()->comment('所属文件');
                $table->string('type')->nullable()->comment('命令类型');
                $table->text('result')->nullable()->comment('命令回调');
                $table->string('to_text')->nullable()->comment('目标语言');
                $table->string('from_text')->nullable()->comment('来源语言');
                $table->integer('status')->default(0)->comment('状态:0=待定,1=生成中,2=完成,3=生成失败');
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
        Schema::dropIfExists('lang_pack_generator_logs');
        Schema::dropIfExists('lang_pack_generator');
    }
};
