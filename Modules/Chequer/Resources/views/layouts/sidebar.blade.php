@php
    $isChequerStandaloneActive = request()->segment(1) === 'chequer';
    $hasChequerRoute = \Illuminate\Support\Facades\Route::has('chequer.dashboard');
@endphp
@if ($hasChequerRoute)
<li class="nav-item {{ $isChequerStandaloneActive ? 'active active-sub' : '' }}">
    <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#chequer-standalone-menu" aria-expanded="{{ $isChequerStandaloneActive ? 'true' : 'false' }}" aria-controls="chequer-standalone-menu">
        <i class="fa fa-pencil-square-o"></i><span>Chequer Module</span>
    </a>
    <div id="chequer-standalone-menu" class="collapse {{ $isChequerStandaloneActive ? 'show' : '' }}" data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Chequer Module:</h6>
            <a class="collapse-item {{ request()->is('chequer') ? 'active' : '' }}" href="{{ route('chequer.dashboard') }}">Dashboard</a>
            <a class="collapse-item {{ request()->is('chequer/bank-accounts*') ? 'active' : '' }}" href="{{ route('chequer.bank-accounts.index') }}">Bank Accounts</a>
            <a class="collapse-item {{ request()->is('chequer/templates*') ? 'active' : '' }}" href="{{ route('chequer.templates.index') }}">Templates</a>
            <a class="collapse-item {{ request()->is('chequer/cheque-books*') ? 'active' : '' }}" href="{{ route('chequer.cheque-books.index') }}">Cheque Books</a>
            <a class="collapse-item {{ request()->is('chequer/cheque-leaves*') || request()->is('chequer/cheque-numbers*') || request()->is('chequer/cheque-number-entries*') ? 'active' : '' }}" href="{{ route('chequer.cheque-leaves.index') }}">Cheque Leaves</a>
            <a class="collapse-item {{ request()->is('chequer/write-cheque*') ? 'active' : '' }}" href="{{ route('chequer.write-cheque.index') }}">Write Cheque</a>
            <a class="collapse-item {{ request()->is('chequer/settings*') || request()->is('chequer/default-settings*') ? 'active' : '' }}" href="{{ route('chequer.settings.index') }}">Settings</a>
        </div>
    </div>
</li>
@endif
