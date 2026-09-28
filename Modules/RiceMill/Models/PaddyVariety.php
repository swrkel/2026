<?php
namespace Modules\RiceMill\Models;

class PaddyVariety extends BaseRiceMillModel
{
    protected $table = 'rcm_paddy_varieties';

    protected $casts = [
        'paddy_product_id' => 'integer',
        'active' => 'boolean',
        'default_moisture_percent' => 'decimal:3',
        'foreign_matter_limit_percent' => 'decimal:3',
        'expected_rice_yield_percent' => 'decimal:3',
        'expected_broken_rice_percent' => 'decimal:3',
        'expected_bran_percent' => 'decimal:3',
        'expected_husk_percent' => 'decimal:3',
        'expected_process_loss_percent' => 'decimal:3',
        'lot_opening_number' => 'integer',
    ];
}
