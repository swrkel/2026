<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CashSettlement extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $table = 'petro_cash_settlements';

    protected $fillable = [
        'daily_cash_status_id',
        'user_id',
        'amount',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function dailyCashStatus()
    {
        return $this->belongsTo(DailyCashStatus::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount, 3, '.', ',');
    }

    // Migration as a static method
    public static function migrate()
    {
        if (!Schema::hasTable('petro_cash_settlements')) {
            Schema::create('petro_cash_settlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('daily_cash_status_id')
                    ->constrained('petro_daily_cash_status')
                    ->onDelete('cascade');
                $table->foreignId('user_id')
                    ->constrained('users')
                    ->onDelete('cascade');
                $table->decimal('amount', 15, 3);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['daily_cash_status_id', 'user_id']);
            });

            return true;
        }
        return false;
    }
}