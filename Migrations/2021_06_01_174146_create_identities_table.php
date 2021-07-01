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
        Capsule::schema()->create('identities', function (Blueprint $table) {
            $table->increments('Id');

            $table->integer('IdUser')->default(0);
            $table->integer('IdAccount')->default(0);
            $table->boolean('Default')->default(false);
            $table->string('Email')->default('');
            $table->string('FriendlyName')->default('');
        });

        $prefix = Capsule::connection()->getTablePrefix();
        Capsule::statement("ALTER TABLE {$prefix}identities ADD Signature MEDIUMBLOB");

        Capsule::schema()->table('identities', function (Blueprint $table) {
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
        Capsule::schema()->dropIfExists('identities');
    }
}
