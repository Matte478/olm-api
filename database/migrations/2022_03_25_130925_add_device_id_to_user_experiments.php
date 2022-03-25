<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeviceIdToUserExperiments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_experiments', function (Blueprint $table) {
            $table->foreignId('device_id')->after('experiment_id')
                ->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_experiments', function (Blueprint $table) {
            $table->dropForeign(['device_id']);
        });
        Schema::table('user_experiments', function (Blueprint $table) {
            $table->dropColumn('device_id');
        });
    }
}
