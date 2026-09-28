<?php
namespace Modules\Ran\Reports;interface RanReport{public function title():string;public function headings():array;public function rows(array $filters):array;public function totals(array $rows):array;}
