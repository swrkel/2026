@php($bankingGroups = app(\Modules\BankingUI\Services\BankingNavigationService::class)->groupsForUser(auth()->user()))
<li class="treeview banking-suite-menu">
    <a href="{{ route('banking.tester-dashboard') }}"><i class="fa fa-university"></i> <span>Banking</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
    <ul class="treeview-menu">
        @foreach($bankingGroups as $group)
            @if(!empty($group['items']))
                <li class="treeview">
                    <a href="#"><i class="fa fa-circle-o"></i> {{ $group['label'] }} <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
                    <ul class="treeview-menu">
                        @foreach($group['items'] as $item)
                            <li><a data-banking-nav="{{ $item['key'] }}" href="{{ isset($item['params']) ? route($item['route'], $item['params']) : route($item['route']) }}"><i class="fa fa-angle-right"></i> {{ $item['label'] }}</a></li>
                        @endforeach
                    </ul>
                </li>
            @endif
        @endforeach
    </ul>
</li>
