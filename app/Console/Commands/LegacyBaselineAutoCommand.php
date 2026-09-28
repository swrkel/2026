<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class LegacyBaselineAutoCommand extends Command
{
    protected $signature = 'legacy:baseline:auto
                            {--execute : Persist baseline records in iterative passes}
                            {--force : Allow execute in production}
                            {--include-columns : Include safe add-column detections}
                            {--max-passes=10 : Maximum auto passes to perform}
                            {--report= : Custom absolute path for report JSON file}';

    protected $description = 'Auto-run safe legacy baseline in batches until no new candidates are found. Dry-run by default.';

    public function handle()
    {
        $execute = (bool) $this->option('execute');
        $includeColumns = (bool) $this->option('include-columns');
        $maxPasses = max(1, (int) $this->option('max-passes'));

        if ($execute && app()->environment('production') && !$this->option('force')) {
            $this->error('Execution in production requires --force.');
            return 1;
        }

        $files = $this->collectMigrationFiles();
        if (empty($files)) {
            $this->error('No migration files found in app or module migration directories.');
            return 1;
        }
        usort($files, function ($a, $b) {
            return strcmp($a->getFilename(), $b->getFilename());
        });

        $mode = $execute ? 'EXECUTE' : 'DRY-RUN';
        $this->info('Legacy baseline auto mode: ' . $mode);
        $this->line('Include columns: ' . ($includeColumns ? 'yes' : 'no'));
        $this->line('Max passes: ' . $maxPasses);

        $allPasses = [];
        $totalInserted = 0;

        for ($pass = 1; $pass <= $maxPasses; $pass++) {
            $existingMigrations = DB::table('migrations')->pluck('migration')->flip();
            $candidates = $this->detectCandidates($files, $existingMigrations, $includeColumns);

            $allPasses[] = [
                'pass' => $pass,
                'count' => count($candidates),
                'items' => $candidates,
            ];

            $this->line('');
            $this->info('Pass ' . $pass . ': ' . count($candidates) . ' candidate(s)');
            foreach ($candidates as $row) {
                $this->line('- ' . $row['migration'] . ' [' . $row['type'] . '] => ' . $row['target']);
            }

            if (count($candidates) === 0) {
                break;
            }

            if (!$execute) {
                // Dry-run mode reports first pass only.
                break;
            }

            $batch = ((int) DB::table('migrations')->max('batch')) + 1;
            DB::transaction(function () use ($candidates, $batch) {
                foreach ($candidates as $row) {
                    DB::table('migrations')->insert([
                        'migration' => $row['migration'],
                        'batch' => $batch,
                    ]);
                }
            });

            $totalInserted += count($candidates);
            $this->info('Inserted in pass ' . $pass . ': ' . count($candidates) . ' (batch ' . $batch . ')');
        }

        $report = [
            'timestamp' => now()->toDateTimeString(),
            'mode' => $execute ? 'execute' : 'dry-run',
            'include_columns' => $includeColumns,
            'max_passes' => $maxPasses,
            'total_inserted' => $totalInserted,
            'passes' => $allPasses,
        ];

        $reportPath = $this->writeReport($report);
        $this->info('Report written: ' . $reportPath);

        return 0;
    }

    private function detectCandidates(array $files, $existingMigrations, $includeColumns)
    {
        $candidates = [];
        foreach ($files as $file) {
            $basename = $this->normalizeMigrationName(pathinfo($file->getFilename(), PATHINFO_FILENAME));
            if (isset($existingMigrations[$basename])) {
                continue;
            }

            $create = $this->parseCreateTableMigration($basename);
            if (!empty($create) && Schema::hasTable($create['table'])) {
                $candidates[] = [
                    'migration' => $basename,
                    'type' => 'create_table',
                    'target' => $create['table'],
                ];
                continue;
            }

            $foreign = $this->parseAddForeignKeysMigration($basename);
            if (!empty($foreign) && Schema::hasTable($foreign['table'])) {
                $candidates[] = [
                    'migration' => $basename,
                    'type' => 'add_foreign_keys',
                    'target' => $foreign['table'],
                ];
                continue;
            }

            if ($includeColumns) {
                $column = $this->parseSingleAddColumnMigration($basename);
                if (!empty($column)
                    && Schema::hasTable($column['table'])
                    && Schema::hasColumn($column['table'], $column['column'])) {
                    $candidates[] = [
                        'migration' => $basename,
                        'type' => 'add_column',
                        'target' => $column['table'] . '.' . $column['column'],
                    ];
                    continue;
                }

                $multiColumn = $this->parseMultiTableAddColumnMigration($basename);
                if (!empty($multiColumn) && $this->allTargetsHaveColumn($multiColumn['tables'], $multiColumn['column'])) {
                    $candidates[] = [
                        'migration' => $basename,
                        'type' => 'add_column_multi_table',
                        'target' => implode(', ', array_map(function ($table) use ($multiColumn) {
                            return $table . '.' . $multiColumn['column'];
                        }, $multiColumn['tables'])),
                    ];
                }
            }

            if ($includeColumns) {
                $contentCandidate = $this->resolveContentColumnOpsCandidate($basename, $file->getPathname());
                if (!empty($contentCandidate)) {
                    $candidates[] = $contentCandidate;
                    continue;
                }
            }

            $dropNoop = $this->parseNoopDropColumnsMigration($file->getPathname());
            if (!empty($dropNoop) && $this->allTargetsDropNoop($dropNoop['table'], $dropNoop['columns'])) {
                $candidates[] = [
                    'migration' => $basename,
                    'type' => 'drop_column_noop',
                    'target' => $dropNoop['table'] . '.[' . implode(', ', $dropNoop['columns']) . ']',
                ];
                continue;
            }
        }

        return $candidates;
    }

    private function collectMigrationFiles()
    {
        $allFiles = [];

        $defaultDir = database_path('migrations');
        if (File::isDirectory($defaultDir)) {
            $allFiles = array_merge($allFiles, File::files($defaultDir));
        }

        $modulesRoot = base_path('Modules');
        if (File::isDirectory($modulesRoot)) {
            $moduleDirs = File::directories($modulesRoot);
            foreach ($moduleDirs as $moduleDir) {
                $moduleMigrationDir = $moduleDir . DIRECTORY_SEPARATOR . 'Database' . DIRECTORY_SEPARATOR . 'Migrations';
                if (File::isDirectory($moduleMigrationDir)) {
                    $allFiles = array_merge($allFiles, File::files($moduleMigrationDir));
                }
            }
        }

        return $allFiles;
    }

    private function parseCreateTableMigration($migration)
    {
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_create_(.+)_table$/', $migration, $m)) {
            return ['table' => $m[1]];
        }
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_create_([a-z0-9_]+)$/', $migration, $m)) {
            return ['table' => $m[1]];
        }
        return null;
    }

    private function parseAddForeignKeysMigration($migration)
    {
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_add_foreign_keys_to_(.+)_table$/', $migration, $m)) {
            return ['table' => $m[1]];
        }
        return null;
    }

    private function parseSingleAddColumnMigration($migration)
    {
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_add_([a-z0-9_]+)_to_([a-z0-9_]+)_table$/', $migration, $m)) {
            return [
                'column' => $m[1],
                'table' => $m[2],
            ];
        }
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_add_([a-z0-9_]+)_to_([a-z0-9_]+)$/', $migration, $m)) {
            return [
                'column' => $m[1],
                'table' => $m[2],
            ];
        }
        return null;
    }

    private function parseMultiTableAddColumnMigration($migration)
    {
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_add_([a-z0-9_]+)_to_([a-z0-9_]+(?:_and_[a-z0-9_]+)+)$/', $migration, $m)) {
            return [
                'column' => $m[1],
                'tables' => explode('_and_', $m[2]),
            ];
        }
        if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_add_([a-z0-9_]+)_to_([a-z0-9_]+(?:_and_[a-z0-9_]+)+)_table$/', $migration, $m)) {
            return [
                'column' => $m[1],
                'tables' => explode('_and_', $m[2]),
            ];
        }
        return null;
    }

    private function allTargetsHaveColumn(array $tables, $column)
    {
        foreach ($tables as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                return false;
            }
        }
        return true;
    }

    private function writeReport(array $report)
    {
        $custom = $this->option('report');
        if (!empty($custom)) {
            File::put($custom, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $custom;
        }

        $reportDir = storage_path('logs');
        if (!File::isDirectory($reportDir)) {
            File::makeDirectory($reportDir, 0755, true);
        }

        $path = $reportDir . DIRECTORY_SEPARATOR . 'legacy_baseline_auto_' . now()->format('Ymd_His') . '.json';
        File::put($path, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $path;
    }

    private function resolveContentColumnOpsCandidate($migration, $filePath)
    {
        $ops = $this->parseTableColumnOpsFromFile($filePath);
        if (empty($ops)) {
            return null;
        }

        foreach ($ops as $op) {
            if ($op['type'] === 'add_columns' && $this->allTargetsHaveColumns($op['table'], $op['columns'])) {
                return [
                    'migration' => $migration,
                    'type' => 'add_columns_content',
                    'target' => $op['table'] . '.[' . implode(', ', $op['columns']) . ']',
                ];
            }

            if ($op['type'] === 'drop_columns' && $this->allTargetsDropNoop($op['table'], $op['columns'])) {
                return [
                    'migration' => $migration,
                    'type' => 'drop_columns_content_noop',
                    'target' => $op['table'] . '.[' . implode(', ', $op['columns']) . ']',
                ];
            }
        }

        $indexOps = $this->parseIndexOpsFromFile($filePath);
        foreach ($indexOps as $indexOp) {
            if ($this->allIndexesExist($indexOp['table'], $indexOp['indexes'])) {
                return [
                    'migration' => $migration,
                    'type' => 'add_indexes_content',
                    'target' => $indexOp['table'] . '.[' . implode(', ', $indexOp['indexes']) . ']',
                ];
            }
        }

        return null;
    }

    private function parseTableColumnOpsFromFile($filePath)
    {
        if (!File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);
        $upContent = $this->extractUpMethodContent($content);
        if (empty($upContent)) {
            return [];
        }
        $ops = [];

        if (preg_match_all("/Schema::table\\('([^']+)'[\\s\\S]*?function\\s*\\([^\\)]*\\)\\s*\\{([\\s\\S]*?)\\}\\s*\\);/m", $upContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $table = trim($match[1]);
                $block = $match[2];

                $addColumns = $this->extractAddedColumnsFromBlock($block);
                if (!empty($addColumns)) {
                    $ops[] = [
                        'type' => 'add_columns',
                        'table' => $table,
                        'columns' => $addColumns,
                    ];
                }

                $dropColumns = $this->extractDroppedColumnsFromBlock($block);
                if (!empty($dropColumns)) {
                    $ops[] = [
                        'type' => 'drop_columns',
                        'table' => $table,
                        'columns' => $dropColumns,
                    ];
                }
            }
        }

        return $ops;
    }

    private function extractAddedColumnsFromBlock($block)
    {
        $excluded = [
            'dropColumn', 'dropForeign', 'dropIndex', 'dropUnique', 'dropPrimary',
            'index', 'unique', 'primary', 'foreign', 'renameColumn',
            'timestamps', 'softDeletes', 'rememberToken',
        ];

        $columns = [];
        if (preg_match_all('/\$table->([a-zA-Z_][a-zA-Z0-9_]*)\(\'([^\']+)\'/', $block, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $method = $match[1];
                $column = $match[2];
                if (in_array($method, $excluded, true)) {
                    continue;
                }
                $columns[] = $column;
            }
        }

        return array_values(array_filter(array_unique($columns)));
    }

    private function extractDroppedColumnsFromBlock($block)
    {
        $columns = [];
        if (preg_match_all("/->dropColumn\\(([^\\)]*)\\)/", $block, $dropMatches, PREG_SET_ORDER)) {
            foreach ($dropMatches as $dropMatch) {
                $expr = trim($dropMatch[1]);
                if (strpos($expr, '[') !== false) {
                    preg_match_all("/'([^']+)'/", $expr, $colMatches);
                    if (!empty($colMatches[1])) {
                        $columns = array_merge($columns, $colMatches[1]);
                    }
                } else {
                    if (preg_match("/'([^']+)'/", $expr, $single)) {
                        $columns[] = $single[1];
                    }
                }
            }
        }

        return array_values(array_filter(array_unique($columns)));
    }

    private function allTargetsHaveColumns($table, array $columns)
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function parseIndexOpsFromFile($filePath)
    {
        if (!File::exists($filePath)) {
            return [];
        }

        $content = File::get($filePath);
        $upContent = $this->extractUpMethodContent($content);
        if (empty($upContent)) {
            return [];
        }

        $ops = [];

        if (preg_match_all("/Schema::table\\('([^']+)'[\\s\\S]*?function\\s*\\([^\\)]*\\)\\s*\\{([\\s\\S]*?)\\}\\s*\\);/m", $upContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $table = trim($match[1]);
                $block = $match[2];
                $indexes = [];
                if (preg_match_all("/->(?:index|unique)\\([^\\)]*,\\s*'([^']+)'\\)/", $block, $idxMatches, PREG_SET_ORDER)) {
                    foreach ($idxMatches as $idxMatch) {
                        $indexes[] = $idxMatch[1];
                    }
                }
                $indexes = array_values(array_filter(array_unique($indexes)));
                if (!empty($indexes)) {
                    $ops[] = [
                        'table' => $table,
                        'indexes' => $indexes,
                    ];
                }
            }
        }

        if (preg_match_all('/CREATE\s+(?:UNIQUE\s+)?INDEX\s+([a-zA-Z0-9_]+)\s+ON\s+`?([a-zA-Z0-9_]+)`?\s*\(/i', $upContent, $rawMatches, PREG_SET_ORDER)) {
            $byTable = [];
            foreach ($rawMatches as $rawMatch) {
                $indexName = $rawMatch[1];
                $table = $rawMatch[2];
                if (!isset($byTable[$table])) {
                    $byTable[$table] = [];
                }
                $byTable[$table][] = $indexName;
            }
            foreach ($byTable as $table => $indexes) {
                $ops[] = [
                    'table' => $table,
                    'indexes' => array_values(array_filter(array_unique($indexes))),
                ];
            }
        }

        return $ops;
    }

    private function allIndexesExist($table, array $indexes)
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        foreach ($indexes as $index) {
            $rows = DB::select('SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?', [$index]);
            if (empty($rows)) {
                return false;
            }
        }

        return true;
    }

    private function parseNoopDropColumnsMigration($filePath)
    {
        if (!File::exists($filePath)) {
            return null;
        }

        $content = File::get($filePath);
        $upContent = $this->extractUpMethodContent($content);
        if (empty($upContent)) {
            return null;
        }
        if (!preg_match("/Schema::table\\('([^']+)'[\\s\\S]*?->dropColumn\\(([^\\)]*)\\)/", $upContent, $m)) {
            return null;
        }

        $table = trim($m[1]);
        $dropExpr = trim($m[2]);
        $columns = [];

        if (strpos($dropExpr, '[') !== false) {
            preg_match_all("/'([^']+)'/", $dropExpr, $colMatches);
            $columns = $colMatches[1] ?? [];
        } else {
            if (preg_match("/'([^']+)'/", $dropExpr, $single)) {
                $columns[] = $single[1];
            }
        }

        $columns = array_values(array_filter(array_unique($columns)));
        if (empty($table) || empty($columns)) {
            return null;
        }

        return [
            'table' => $table,
            'columns' => $columns,
        ];
    }

    private function allTargetsDropNoop($table, array $columns)
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn($table, $column)) {
                return false;
            }
        }

        return true;
    }

    private function extractUpMethodContent($content)
    {
        if (preg_match('/public function up\s*\([^)]*\)\s*(?::\s*void)?\s*\{([\s\S]*?)(?:public function down|\z)/', $content, $m)) {
            return $m[1];
        }
        return null;
    }

    private function normalizeMigrationName($name)
    {
        while (substr($name, -4) === '.php') {
            $name = substr($name, 0, -4);
        }
        return $name;
    }
}
