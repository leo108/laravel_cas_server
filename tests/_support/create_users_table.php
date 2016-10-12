<?php
/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/9/29
 * Time: 09:57
 */
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cas_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('real_name');
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
        Schema::drop('cas_users');
    }
}
