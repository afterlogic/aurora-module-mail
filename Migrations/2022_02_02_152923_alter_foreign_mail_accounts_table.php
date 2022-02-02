<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AlterForeignMailAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
            $table->dropForeign(['ServerId']);
            $table->foreign('ServerId')->references('Id')->on('mail_servers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
            $table->dropForeign(['ServerId']);
            $table->foreign('ServerId')->references('Id')->on('mail_servers');
        });
    }
}
