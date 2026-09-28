<div class="atn-tabs">
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.reissues.*') ? 'active':'' }}" href="{{ route('airline-ticketing-new.reissues.index') }}">{{ __('airlineticketingnew::postticket.reissues') }}</a>
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.voids.*') ? 'active':'' }}" href="{{ route('airline-ticketing-new.voids.index') }}">{{ __('airlineticketingnew::postticket.voids') }}</a>
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.cancellations.*') ? 'active':'' }}" href="{{ route('airline-ticketing-new.cancellations.index') }}">{{ __('airlineticketingnew::postticket.cancellations') }}</a>
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.refunds.*') ? 'active':'' }}" href="{{ route('airline-ticketing-new.refunds.index') }}">{{ __('airlineticketingnew::postticket.refunds') }}</a>
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.credit-notes.*') ? 'active':'' }}" href="{{ route('airline-ticketing-new.credit-notes.index') }}">{{ __('airlineticketingnew::postticket.credit_notes') }}</a>
</div>
