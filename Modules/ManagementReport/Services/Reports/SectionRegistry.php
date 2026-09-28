<?php
namespace Modules\ManagementReport\Services\Reports;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class SectionRegistry
{
    protected $app;
    protected $definitions;

    public function __construct(Container $app, array $definitions)
    {
        $this->app = $app;
        uasort($definitions, function ($a, $b) {
            return (int) ($a['order'] ?? 0) <=> (int) ($b['order'] ?? 0);
        });
        $this->definitions = $definitions;
    }

    public function definitions()
    {
        return $this->definitions;
    }

    public function selected(array $keys)
    {
        return array_intersect_key($this->definitions, array_flip($keys));
    }

    public function service($key)
    {
        if (!isset($this->definitions[$key]['service'])) {
            throw new InvalidArgumentException('Unknown management report section: ' . $key);
        }
        return $this->app->make($this->definitions[$key]['service']);
    }
}
