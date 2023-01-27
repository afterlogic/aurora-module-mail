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
        Capsule::schema()->create('mail_servers', function (Blueprint $table) {
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
            $table->string('OwnerType')->default(\Aurora\Modules\Mail\Enums\ServerOwnerType::Account);
            $table->text('Domains')->nullable();
            $table->boolean('EnableSieve')->default(false);
            $table->integer('SievePort')->default(4190);
            $table->boolean('EnableThreading')->default(true);
            $table->boolean('UseFullEmailAddressAsLogin')->default(true);

            $table->boolean('SetExternalAccessServers')->default(false);
            $table->string('ExternalAccessImapServer')->default('');
            $table->integer('ExternalAccessImapPort')->default(143);
            $table->integer('ExternalAccessImapAlterPort')->default(0);
            $table->string('ExternalAccessSmtpServer')->default('');
            $table->integer('ExternalAccessSmtpPort')->default(25);
            $table->integer('ExternalAccessSmtpAlterPort')->default(0);
            $table->string('ExternalAccessPop3Server')->default('');
            $table->integer('ExternalAccessPop3Port')->default(110);
            $table->integer('ExternalAccessPop3AlterPort')->default(0);

            $table->boolean('OAuthEnable')->default(false);
            $table->string('OAuthName')->default('');
            $table->string('OAuthType')->default('');
            $table->string('OAuthIconUrl')->default('');

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
        Capsule::schema()->dropIfExists('mail_servers');
    }
}
