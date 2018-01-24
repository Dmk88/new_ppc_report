<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeReportScheduleIdGAReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('g_a_reports', function (Blueprint $table) {
            $table->integer('report_schedule_id')->unsigned()->change();
            $table->foreign('report_schedule_id')->references('id')->on('g_a_reports_schedules')->onDelete('cascade');
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('g_a_reports', function (Blueprint $table) {
            $table->dropForeign(['report_schedule_id']);
            $table->dropIndex('g_a_reports_report_schedule_id_foreign');
        });
    }
}
