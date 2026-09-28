<?php
namespace Modules\EggManagement\Tests\Feature;
use Tests\TestCase;
class EggIsolationTest extends TestCase
{
    public function test_module_tables_follow_egg_prefix(){ $files=glob(__DIR__.'/../../Database/Migrations/*.php');$text=implode("\n",array_map('file_get_contents',$files));preg_match_all("/create\\('([^']+)'/",$text,$m);foreach($m[1] as $table){$this->assertStringStartsWith('egg_',$table);} }
}
