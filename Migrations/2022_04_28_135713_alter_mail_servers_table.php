<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AlterMailServersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->table('mail_servers', function (Blueprint $table) {
            $table->boolean('ExternalAccessImapUseSsl')->default(false);
            $table->boolean('ExternalAccessPop3UseSsl')->default(false);
            $table->boolean('ExternalAccessSmtpUseSsl')->default(false);
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
            $table->dropColumn('ExternalAccessImapUseSsl');
            $table->dropColumn('ExternalAccessPop3UseSsl');
            $table->dropColumn('ExternalAccessSmtpUseSsl');
        });
    }
}
