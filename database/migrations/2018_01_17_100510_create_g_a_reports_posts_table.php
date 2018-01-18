<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGAReportsPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('g_a_reports_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('post_name', 100);
            $table->string('post_url', 250);
            $table->integer('post_wp_id')->length(10)->unsigned();
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
        Schema::dropIfExists('g_a_reports_posts');
    }
}
