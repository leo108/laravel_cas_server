<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProxyGrantingTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cas_proxy_granting_tickets', function (Blueprint $table) {
            $table->charset = 'utf8';
            $table->collation = 'utf8_general_ci';
            $table->increments('id');
            $table->string('ticket', 256)->unique();
            $table->string('pgt_url', 1024);
            $table->integer('service_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->text('proxies')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->foreign('service_id')->references('id')->on('cas_services');
            $table->foreign('user_id')->references(config('cas.user_table.id'))->on(config('cas.user_table.name'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('cas_proxy_granting_tickets');
    }
}
