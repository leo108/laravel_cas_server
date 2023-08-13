<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceHostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cas_service_hosts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('host')->charset('utf8')->collate('utf8_general_ci')->unique();
            $table->integer('service_id')->unsigned();
            $table->foreign('service_id')->references('id')->on('cas_services');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cas_service_hosts');
    }
}
