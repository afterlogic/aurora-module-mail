<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateIdentitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('mail_identities', function (Blueprint $table) {
            $table->increments('Id');

            $table->integer('IdUser')->default(0);
            $table->integer('IdAccount')->default(0);
            $table->boolean('Default')->default(false);
            $table->string('Email')->default('');
            $table->string('FriendlyName')->default('');
            $table->boolean('UseSignature')->default(false);
        });

        $prefix = Capsule::connection()->getTablePrefix();
        Capsule::connection()->statement("ALTER TABLE {$prefix}mail_identities ADD Signature MEDIUMBLOB");

        Capsule::schema()->table('mail_identities', function (Blueprint $table) {
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
        Capsule::schema()->dropIfExists('mail_identities');
    }
}
