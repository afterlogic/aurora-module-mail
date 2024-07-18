<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AlterMailServersTableAddPropertiesColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->table('mail_servers', function (Blueprint $table) {
            $table->json('Properties')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->table('mail_servers', function (Blueprint $table) {
            $table->dropColumn('Properties');
        });
    }
}
