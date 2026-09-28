<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Tenant;

class LoginViewConstantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Set tenant database prefix to empty so we can use the testing database name as tenant ID
        putenv("TENANT_DATABASE_PREFIX=");
        
        $activeDbName = config('database.connections.mysql.database');
        
        $tenant = Tenant::find($activeDbName);
        if (!$tenant) {
            $tenant = Tenant::create([
                'id' => $activeDbName,
                'tenancy_db_name' => $activeDbName
            ]);
        }
        
        $domain = $tenant->domains()->where('domain', 'tenant.localhost')->first();
        if (!$domain) {
            $tenant->domains()->create(['domain' => 'tenant.localhost']);
        }
    }

    protected function tearDown(): void
    {
        $activeDbName = config('database.connections.mysql.database');
        $tenant = Tenant::find($activeDbName);
        if ($tenant) {
            $tenant->domains()->where('domain', 'tenant.localhost')->delete();
            $tenant->delete();
        }
        
        putenv("TENANT_DATABASE_PREFIX");
        parent::tearDown();
    }

    public function testLoginViewRendersInCgiContext(): void
    {
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        
        $env = array_merge($_ENV, [
            'REDIRECT_STATUS' => '200',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/login',
            'SCRIPT_FILENAME' => base_path('index.php'),
            'SCRIPT_NAME' => '/index.php',
            'HTTP_HOST' => 'tenant.localhost',
        ]);
        
        $process = proc_open('/opt/homebrew/bin/php-cgi', $descriptorSpec, $pipes, base_path(), $env);
        
        $this->assertTrue(is_resource($process), 'Failed to start php-cgi process');
        
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);
        
        $this->assertStringNotContainsString('Undefined constant &quot;STDERR&quot;', $stdout);
        $this->assertStringNotContainsString('Undefined constant "STDERR"', $stdout . $stderr);
    }
}
