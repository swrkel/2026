<?php

use Illuminate\Database\Migrations\Migration;
use Modules\ManagementReport\Services\Installation\TenantSchemaInstaller;
use Modules\ManagementReport\Support\TenantConnection;

class CreateMgmtManagementReportTables extends Migration
{
    public function up()
    {
        TenantConnection::assertInitialized();
        app(TenantSchemaInstaller::class)->install();
    }

    public function down()
    {
        TenantConnection::assertInitialized();
        app(TenantSchemaInstaller::class)->uninstall();
    }
}
