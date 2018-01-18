<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClusterPostPivotTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cluster_post', function (Blueprint $table) {
            $table->integer('cluster_id')->unsigned()->nullable();
            $table->foreign('cluster_id')->references('id')->on('g_a_reports_clusters')->onDelete('cascade');
            
            $table->integer('post_id')->unsigned()->nullable();
            $table->foreign('post_id')->references('id')->on('g_a_reports_posts')->onDelete('cascade');
            
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
        Schema::dropIfExists('cluster_post');
    }
}
