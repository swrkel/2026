<?php
namespace Modules\ManagementReport\Support;

class SchemaGuard
{
    protected $tables = [];
    protected $columns = [];

    public function table($table)
    {
        if (!array_key_exists($table, $this->tables)) {
            $this->tables[$table] = TenantConnection::schema()->hasTable($table);
        }
        return $this->tables[$table];
    }

    public function column($table, $column)
    {
        $key = $table . '.' . $column;
        if (!array_key_exists($key, $this->columns)) {
            $this->columns[$key] = $this->table($table) && TenantConnection::schema()->hasColumn($table, $column);
        }
        return $this->columns[$key];
    }

    public function firstColumn($table, array $candidates)
    {
        foreach ($candidates as $column) {
            if ($this->column($table, $column)) {
                return $column;
            }
        }
        return null;
    }
}
