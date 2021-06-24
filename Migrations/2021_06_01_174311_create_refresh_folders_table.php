<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateRefreshFoldersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('refresh_folders', function (Blueprint $table) {
            $table->increments('Id');

            $table->integer('IdAccount')->default(0);
            $table->string('FolderFullName')->default('');
            $table->boolean('AlwaysRefresh')->default(0);

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
        Capsule::schema()->dropIfExists('refresh_folders');
    }
}
