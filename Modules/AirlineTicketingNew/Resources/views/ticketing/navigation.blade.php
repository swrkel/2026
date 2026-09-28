<div class="atn-tabs">
@can('airline_ticketing_new.tickets.view')
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.tickets.*') ? 'active' : '' }}" href="{{ route('airline-ticketing-new.tickets.index') }}">{{ __('airlineticketingnew::ticketing.tickets') }}</a>
@endcan
@can('airline_ticketing_new.invoices.view')
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.invoices.*') ? 'active' : '' }}" href="{{ route('airline-ticketing-new.invoices.index') }}">{{ __('airlineticketingnew::ticketing.invoices') }}</a>
@endcan
@can('airline_ticketing_new.payments.view')
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.payments.*') ? 'active' : '' }}" href="{{ route('airline-ticketing-new.payments.index') }}">{{ __('airlineticketingnew::ticketing.payments') }}</a>
@endcan
</div>
