<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class CreateMailIndexes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
            $table->index('IdUser');
        });

        Capsule::schema()->table('mail_identities', function (Blueprint $table) {
            $table->index('IdUser');
            $table->index('IdAccount');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
            $table->dropIndex(['IdUser']);
        });

        Capsule::schema()->table('mail_identities', function (Blueprint $table) {
            $table->dropIndex(['IdUser']);
            $table->dropIndex(['IdAccount']);
        });
    }
}
