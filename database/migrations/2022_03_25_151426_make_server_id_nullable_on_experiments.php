<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeServerIdNullableOnExperiments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('experiments', function (Blueprint $table) {
            $table->unsignedBigInteger('server_id')->unsigned()->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('experiments', function (Blueprint $table) {
            $table->unsignedBigInteger('server_id')->unsigned()->nullable(false)->change();
        });
    }
}
