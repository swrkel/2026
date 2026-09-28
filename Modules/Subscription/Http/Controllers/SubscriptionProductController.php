<?php
namespace Modules\Subscription\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SubscriptionProductController extends Controller
{
    public function index()
    {
        $products = DB::table('subscription_product')
            ->leftJoin('users', 'subscription_product.created_by', '=', 'users.id')
            ->select(
                'subscription_product.*',
                'users.first_name as created_user'
            )
            ->orderBy('subscription_product.id', 'desc')
            ->get();

        return view('subscription::subscription_products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $last = DB::table('subscription_product')->latest('id')->first();
        $next = $last ? intval(preg_replace('/\D/', '', $last->code)) + 1 : 1;

        DB::table('subscription_product')->insert([
            'code'                 => 'SUB-' . $next,
            'subscription_product' => $request->subscription_product,
            'price'                => $request->base_amount,
            'subscription_period'  => $request->subscription_period,
            'created_by'           => auth()->id(),
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        return redirect()->back()->with('success', 'Subscription Product Added Successfully!');
    }

    public function getPrice(Request $request)
    {
        $productId = $request->id;

        $product = DB::table('subscription_product')->where('id', $productId)->first();

        if (! $product) {
            Log::warning("Subscription product with ID {$productId} not found.");
            return response()->json(['price' => 0]);
        }

        return response()->json(['price' => $product->price]);
    }

}
