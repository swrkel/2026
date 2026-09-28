<?php

namespace Modules\BankingTesterUI\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use Modules\BankingTesterUI\Services\BankingTesterNavigationService;

class BankingTesterDashboardController extends Controller
{
    protected BankingTesterNavigationService $navigation;

    public function __construct(BankingTesterNavigationService $navigation)
    {
        $this->navigation = $navigation;
    }

    public function index()
    {
        return view('bankingtesterui::dashboard.index', ['modules' => $this->navigation->modules(), 'title' => 'Banking Tester UI']);
    }

    public function modules()
    {
        return view('bankingtesterui::modules.index', ['modules' => $this->navigation->modules(), 'title' => 'Banking Module Pages']);
    }

    public function module(string $slug)
    {
        $module = $this->navigation->moduleBySlug($slug) ?: ['name' => 'Banking Module', 'status' => 'Not registered', 'items' => []];
        return view('bankingtesterui::modules.show', ['module' => $module, 'title' => $module['name']]);
    }

    public function checklist()
    {
        return view('bankingtesterui::dashboard.checklist', ['items' => $this->navigation->checklist(), 'title' => 'Banking Testing Checklist']);
    }

    public function routeHealth()
    {
        $routes = collect(Route::getRoutes())->filter(function ($route) {
            return str_starts_with($route->uri(), 'banking');
        })->map(function ($route) {
            return ['method' => implode('|', $route->methods()), 'uri' => $route->uri(), 'name' => $route->getName()];
        })->values();

        return view('bankingtesterui::dashboard.route-health', ['routes' => $routes, 'title' => 'Banking Route Health']);
    }

    public function reports()
    {
        return view('bankingtesterui::reports.index', ['modules' => $this->navigation->modules(), 'title' => 'Banking Reports Shell']);
    }

    public function settings()
    {
        return view('bankingtesterui::settings.index', ['modules' => $this->navigation->modules(), 'title' => 'Banking Settings Shell']);
    }
}
