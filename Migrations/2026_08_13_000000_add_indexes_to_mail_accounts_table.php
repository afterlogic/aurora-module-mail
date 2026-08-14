<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class AddIndexesToMailAccountsTable extends Migration
{
    public function up()
    {
        $prefix = Capsule::connection()->getTablePrefix();

        $sm = Capsule::connection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($prefix . 'mail_accounts');

        if (!$doctrineTable->hasIndex('mail_accounts_email_index')) {
            Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
                $table->index(['Email', 'IsDisabled'], 'mail_accounts_email_index');
            });
        }

        if (!$doctrineTable->hasIndex('mail_accounts_incominglogin_index')) {
            Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
                $table->index('IncomingLogin', 'mail_accounts_incominglogin_index');
            });
        }
    }

    public function down()
    {
        $prefix = Capsule::connection()->getTablePrefix();

        $sm = Capsule::connection()->getDoctrineSchemaManager();
        $doctrineTable = $sm->listTableDetails($prefix . 'mail_accounts');

        if ($doctrineTable->hasIndex('mail_accounts_email_index')) {
            Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
                $table->dropIndex('mail_accounts_email_index');
            });
        }

        if ($doctrineTable->hasIndex('mail_accounts_incominglogin_index')) {
            Capsule::schema()->table('mail_accounts', function (Blueprint $table) {
                $table->dropIndex('mail_accounts_incominglogin_index');
            });
        }
    }
}
