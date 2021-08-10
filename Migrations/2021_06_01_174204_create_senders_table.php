<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateSendersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('mail_trusted_senders', function (Blueprint $table) {
            $table->increments('Id');

            $table->integer('IdUser')->default(0);
            $table->string('Email')->default('');

            $table->timestamp(\Aurora\System\Classes\Model::CREATED_AT)->nullable();
            $table->timestamp(\Aurora\System\Classes\Model::UPDATED_AT)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->dropIfExists('mail_trusted_senders');
    }
}
