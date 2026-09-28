<?php
namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautyRetailSale;
use Modules\BeautySaloons\Entities\BeautyRetailSaleLine;

class BeautyRetailSaleService
{
    public function createSale(array $header, array $lines): BeautyRetailSale
    {
        return DB::transaction(function () use ($header, $lines) {
            $sale = BeautyRetailSale::create($header);
            foreach ($lines as $line) {
                $line['retail_sale_id'] = $sale->id;
                BeautyRetailSaleLine::create($line);
            }
            return $sale;
        });
    }
}
