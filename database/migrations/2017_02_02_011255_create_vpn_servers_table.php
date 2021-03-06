<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateVpnServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vpn_servers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cf_id')->unique();
            $table->string('server_key')->unique();
            $table->ipAddress('server_ip')->unique();
            $table->string('server_port');
            $table->string('vpn_secret');
            $table->string('server_domain')->unique();
            $table->integer('web_port')->default(80);
            $table->string('server_name')->unique();
            $table->tinyInteger('is_active')->unsigned();
            $table->integer('server_access_id');
            $table->integer('user_id')->unsigned()->default(0);
            $table->tinyInteger('limit_bandwidth')->unsigned()->default(0);
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
        Schema::dropIfExists('vpn_servers');
    }
}
