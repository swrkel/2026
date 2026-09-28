<?php
namespace Modules\EggManagement\Integrations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\EggManagement\Services\EggContext;

abstract class DirectoryGateway
{
    protected $context;
    public function __construct(EggContext $context) { $this->context = $context; }
    protected function db() { return DB::connection(config('egg.connection')); }
    protected function hasTable($table)
    {
        return Schema::connection(config('egg.connection') ?: config('database.default'))->hasTable($table);
    }
}
