<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class UpdateMailIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->table('mail_refresh_folders', function (Blueprint $table) {
            $table->index('IdAccount');
        });
        Capsule::schema()->table('mail_servers', function (Blueprint $table) {
            $table->index('TenantId');
        });
        Capsule::schema()->table('mail_system_folders', function (Blueprint $table) {
            $table->index('IdAccount');
        });
        Capsule::schema()->table('mail_trusted_senders', function (Blueprint $table) {
            $table->index('IdUser');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->table('mail_refresh_folders', function (Blueprint $table) {
            $table->dropIndex(['IdAccount']);
        });
        Capsule::schema()->table('mail_servers', function (Blueprint $table) {
            $table->dropIndex(['TenantId']);
        });
        Capsule::schema()->table('mail_system_folders', function (Blueprint $table) {
            $table->dropIndex(['IdAccount']);
        });
        Capsule::schema()->table('mail_trusted_senders', function (Blueprint $table) {
            $table->dropIndex(['IdUser']);
        });
    }
}
