<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGAReportsDatasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('g_a_reports_datas', function (Blueprint $table) {
            $table->increments('id');
            $table->string('data_path', 250);
            $table->string('data_source', 50);
            $table->integer('data_page_views');
            $table->integer('data_unique_page_views');
            $table->float('data_bounce_rate', 5, 2);
            $table->float('data_avg_session_duration', 10, 2);
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
        Schema::dropIfExists('g_a_reports_datas');
    }
}
