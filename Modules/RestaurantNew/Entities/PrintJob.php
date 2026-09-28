<?php
namespace Modules\RestaurantNew\Entities;

class PrintJob extends RestnewModel
{
    protected $table = 'restnew_print_jobs';
    protected $casts = [
        'payload' => 'array',
        'printed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
