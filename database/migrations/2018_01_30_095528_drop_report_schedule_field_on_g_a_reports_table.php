<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropReportScheduleFieldOnGAReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('g_a_reports', function (Blueprint $table) {
            $table->dropColumn('report_schedule');
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
            $table->string('report_schedule', 100);
        });
    }
}
