<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsPostsPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reports_posts', function (Blueprint $table) {
            $table->integer('report_id')->unsigned()->nullable();
            $table->foreign('report_id')->references('id')->on('g_a_reports')->onDelete('cascade');
    
            $table->integer('post_id')->unsigned()->nullable();
            $table->foreign('post_id')->references('id')->on('g_a_reports_posts')->onDelete('cascade');
    
            $table->integer('report_data_id')->unsigned()->nullable();
            $table->foreign('report_data_id')->references('id')->on('g_a_reports_datas')->onDelete('cascade');
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
        Schema::dropIfExists('reports_posts');
    }
}
