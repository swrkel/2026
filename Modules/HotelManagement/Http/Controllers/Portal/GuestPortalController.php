<?php
namespace Modules\HotelManagement\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\HotelManagement\Services\Portal\GuestPortalService;

class GuestPortalController extends Controller
{
    public function __construct(protected GuestPortalService $portal) {}
    public function login() { return view('hotelmanagement::portal.login'); }
    public function authenticate(Request $request) { return back()->with('status', __('hotelmanagement::hotel.portal_login_ready')); }
    public function dashboard() { return view('hotelmanagement::portal.dashboard', $this->portal->dashboard(Auth::id())); }
    public function reservations() { return view('hotelmanagement::portal.reservations', $this->portal->dashboard(Auth::id())); }
    public function profile() { return view('hotelmanagement::portal.profile'); }
    public function folios() { return view('hotelmanagement::portal.folios', $this->portal->dashboard(Auth::id())); }
    public function requests() { return view('hotelmanagement::portal.requests'); }
}
