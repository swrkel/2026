<div class="atn-tabs atn-master-tabs">
@foreach([
'airlines'=>'Airlines','airports'=>'Airports','aircraft-types'=>'Aircraft Types','travel-classes'=>'Travel Classes',
'routes'=>'Routes','suppliers'=>'Suppliers','agents'=>'Agents','currencies'=>'Currencies',
'tax-rules'=>'Tax Rules','commission-rules'=>'Commission Rules'
] as $route => $label)
@can('airline_ticketing_new.' . $route . '.manage')
<a class="atn-tab {{ request()->routeIs('airline-ticketing-new.masters.' . $route . '.*') ? 'active' : '' }}"
href="{{ route('airline-ticketing-new.masters.' . $route . '.index') }}">{{ $label }}</a>
@endcan
@endforeach
</div>
