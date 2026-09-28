{{--
    Module tab strip.

    The active tab is worked out from the current route name rather than from a
    variable each page has to remember to pass - a page that forgot would show
    no tab as active, and that is the kind of thing nobody notices for months.
--}}
@php
    $chcCurrent = request()->route() ? (string) request()->route()->getName() : '';

    $chcTabs = [
        ['route' => 'churchmanagement.dashboard',       'label' => __('churchmanagement::lang.dashboard'), 'icon' => 'fa fa-dashboard'],
        ['route' => 'churchmanagement.members.index',   'label' => __('churchmanagement::lang.members'),   'icon' => 'fa fa-users'],
        ['route' => 'churchmanagement.families.index',  'label' => __('churchmanagement::lang.families'),  'icon' => 'fa fa-home'],
        ['route' => 'churchmanagement.donations.index', 'label' => __('churchmanagement::lang.donations'), 'icon' => 'fa fa-gift'],
        ['route' => 'churchmanagement.attendance.index','label' => __('churchmanagement::lang.attendance'),'icon' => 'fa fa-check-square-o'],
        ['route' => 'churchmanagement.events.index',    'label' => __('churchmanagement::lang.events'),    'icon' => 'fa fa-calendar'],
        ['route' => 'churchmanagement.settings.index',  'label' => __('churchmanagement::lang.settings'),  'icon' => 'fa fa-cog'],
    ];
@endphp

<div class="chc-nav">
    @foreach($chcTabs as $chcTab)
        <a href="{{ route($chcTab['route']) }}"
           {{--
               str_starts_with, not equality: the attendance register opens under
               churchmanagement.attendance.* and its tab must stay highlighted
               rather than the strip appearing to have nothing selected.
           --}}
           class="{{ str_starts_with($chcCurrent, str_replace('.index', '', $chcTab['route'])) ? 'active' : '' }}">
            <i class="{{ $chcTab['icon'] }}"></i> {{ $chcTab['label'] }}
        </a>
    @endforeach
</div>
