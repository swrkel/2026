<div class="atn-tabs">
@can('airline_ticketing_new.passengers.manage')
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.passengers.*') ? 'active' : '' }}"
   href="{{ route('airline-ticketing-new.passengers.index') }}">{{ __('airlineticketingnew::profiles.passengers') }}</a>
@endcan
@can('airline_ticketing_new.corporate_customers.manage')
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.corporate-customers.*') ? 'active' : '' }}"
   href="{{ route('airline-ticketing-new.corporate-customers.index') }}">{{ __('airlineticketingnew::profiles.corporate_customers') }}</a>
@endcan
@can('airline_ticketing_new.reports.document_expiry')
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.reports.document-expiry') ? 'active' : '' }}"
   href="{{ route('airline-ticketing-new.reports.document-expiry') }}">{{ __('airlineticketingnew::profiles.document_expiry_report') }}</a>
@endcan
</div>
