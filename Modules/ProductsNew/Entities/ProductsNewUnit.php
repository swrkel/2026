<?php
namespace Modules\ProductsNew\Entities;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * MA-002: SoftDeletes added.
 *
 * This is a standalone model on the shared `units` table. Core's App\Unit
 * uses SoftDeletes; this copy did not. Two consequences:
 *
 *   1. Queries through it INCLUDED rows deleted elsewhere in the system.
 *   2. ->delete() would have removed the row permanently, while the rest of
 *      the application expects a deleted `units` row to remain recoverable.
 *
 * SAFE TO APPLY NOW - checked before changing anything:
 *   `units` currently holds ZERO rows with deleted_at set in the tenant
 *   database, so the trait cannot change the result of any query running
 *   today. ProductsNew also makes no create, update or delete calls through
 *   this class at present, so nothing in its behaviour shifts either.
 *
 * It closes the hole before ProductsNew starts writing here - which it will,
 * once the legacy Product module retires.
 *
 * LogsActivity is deliberately NOT added. Core's model logs activity, but
 * that belongs to whichever module OWNS the writes. Adding it to a currently
 * read-only copy would risk duplicate or misleading audit entries. Revisit
 * when ProductsNew takes ownership of writing to this table.
 */
class ProductsNewUnit extends Model
{
    use SoftDeletes;

    protected $table = 'units';

    protected $guarded = ['id'];
}
