<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->create('servers', function (Blueprint $table) {
            $table->increments('Id');

            $table->integer('TenantId')->default(0);
            $table->string('Name')->default('');
            $table->string('IncomingServer')->default('');
            $table->integer('IncomingPort')->default(143);
            $table->boolean('IncomingUseSsl')->default(false);
            $table->string('OutgoingServer')->default('');
            $table->integer('OutgoingPort')->default(25);
            $table->boolean('OutgoingUseSsl')->default(false);
            $table->string('SmtpAuthType')->default(\Aurora\Modules\Mail\Enums\SmtpAuthType::NoAuthentication);
            $table->string('SmtpLogin')->default('');
            $table->string('SmtpPassword')->default(''); // field type encrypted?
            $table->string('OwnerType')->default( \Aurora\Modules\Mail\Enums\ServerOwnerType::Account);
            $table->text('Domains')->nullable();
            $table->boolean('EnableSieve')->default(false);
            $table->integer('SievePort')->default(4190);
            $table->boolean('EnableThreading')->default(true);
            $table->boolean('UseFullEmailAddressAsLogin')->default(true);
            $table->boolean('SetExternalAccessServers')->default(false);
            $table->string('ExternalAccessImapServer')->default('');
            $table->integer('ExternalAccessImapPort')->default(143);
            $table->string('ExternalAccessSmtpServer')->default('');
            $table->integer('ExternalAccessSmtpPort')->default(25);
            $table->boolean('OAuthEnable')->default(false);
            $table->string('OAuthName')->default('');
            $table->string('OAuthType')->default('');
            $table->string('OAuthIconUrl')->default('');

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
        Capsule::schema()->dropIfExists('servers');
    }
}
