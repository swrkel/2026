<?php



namespace Modules\Airline\Entities;



use App\User;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\SoftDeletes;




class AirlineCustomers extends Model

{

    /*
     * MA-002: SoftDeletes restored.
     *
     * This model is a standalone copy on the shared `contacts` table. Core's
     * App\Contact uses SoftDeletes; this copy did not, so ->delete() through
     * it was a HARD delete and its queries included rows deleted elsewhere.
     *
     * Safe to apply now: `contacts` currently holds ZERO rows with deleted_at
     * set in the tenant database I was given, so the trait cannot change any
     * existing query result. It only prevents future hard deletes and stale
     * reads.
     */
    use SoftDeletes;

    use HasFactory;

    protected $table = 'contacts';

    public $timestamps = false;

    protected $fillable = ['business_id'];


    // protected $fillable = [

    //     'type_name',

    //     'description',

      
    // ];



    public function user()

    {

        return $this->belongsTo(User::class);

    }



}

