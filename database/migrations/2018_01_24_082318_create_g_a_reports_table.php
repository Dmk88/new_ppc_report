<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGAReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('g_a_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->string('report_name', 70);
            $table->string('report_start_date_range', 10);
            $table->string('report_end_date_range', 10);
            $table->string('report_schedule', 100);
            $table->boolean('report_active')->default(0);
            $table->integer('report_schedule_id');
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('g_a_reports');
    }
}
