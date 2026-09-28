<?php
namespace Modules\HotelManagement\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\Api\HotelApiService;

class GuestApiController extends Controller
{
    public function __construct(protected HotelApiService $api) {}
    public function login(Request $request) { return response()->json(['message' => 'Hotel guest API login scaffold ready']); }
    public function branches() { return response()->json(['data' => $this->api->branches()]); }
    public function roomTypes() { return response()->json(['data' => $this->api->roomTypes()]); }
    public function availability(Request $request) { return response()->json(['data' => $this->api->availability($request->all())]); }
    public function profile(Request $request) { return response()->json(['data' => $request->user()]); }
    public function reservations(Request $request) { return response()->json(['data' => []]); }
    public function storeReservation(Request $request) { return response()->json(['message' => 'Reservation API scaffold ready'], 201); }
    public function folios(Request $request) { return response()->json(['data' => []]); }
    public function notifications(Request $request) { return response()->json(['data' => []]); }
}
