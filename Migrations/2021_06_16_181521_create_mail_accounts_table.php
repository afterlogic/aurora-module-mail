<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateMailAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('mail_accounts', function (Blueprint $table) {
            $table->increments('Id');

            $table->boolean('IsDisabled')->default(false);
            $table->integer('IdUser')->default(0);
            $table->boolean('UseToAuthorize')->default(false);

            $table->string('Email')->default('');
            $table->string('FriendlyName')->default('');
            $table->string('IncomingLogin')->default('');
            $table->string('IncomingPassword')->default('');

            $table->boolean('IncludeInUnifiedMailbox')->default(false);
            $table->boolean('UseSignature')->default(false);
        });

        $prefix = Capsule::connection()->getTablePrefix();
        Capsule::connection()->statement("ALTER TABLE {$prefix}mail_accounts ADD Signature MEDIUMBLOB");

        Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
            $table->unsignedInteger('ServerId')->default(0);
            $table->foreign('ServerId')->references('Id')->on('mail_servers');
        });

        Capsule::connection()->statement("ALTER TABLE {$prefix}mail_accounts ADD FoldersOrder MEDIUMBLOB");

        Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
            $table->boolean('UseThreading')->default(false);
            $table->boolean('SaveRepliesToCurrFolder')->default(false);
            $table->boolean('ShowUnifiedMailboxLabel')->default(false);

            $table->string('UnifiedMailboxLabelText')->default('');
            $table->string('UnifiedMailboxLabelColor')->default('');
            $table->string('XOAuth')->nullable();

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
        Capsule::schema()->dropIfExists('mail_accounts');
    }
}
