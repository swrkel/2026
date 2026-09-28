<div class="atn-tabs">
@can('airline_ticketing_new.quotations.view')
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.quotations.*') ? 'active' : '' }}"
   href="{{ route('airline-ticketing-new.quotations.index') }}">{{ __('airlineticketingnew::transactions.quotations') }}</a>
@endcan
@can('airline_ticketing_new.reservations.view')
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.reservations.*') ? 'active' : '' }}"
   href="{{ route('airline-ticketing-new.reservations.index') }}">{{ __('airlineticketingnew::transactions.reservations') }}</a>
@endcan
</div>
