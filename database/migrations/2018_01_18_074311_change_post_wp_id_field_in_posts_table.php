<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePostWpIdFieldInPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('g_a_reports_posts', function (Blueprint $table) {
            $table->integer('post_wp_id')->length(10)->unsigned()->unique()->change();
        });
    }
    
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('g_a_reports_posts', function (Blueprint $table) {
            $table->dropUnique(['post_wp_id']);
            $table->integer('post_wp_id')->length(10)->unsigned()->change();
        });
    }
}
