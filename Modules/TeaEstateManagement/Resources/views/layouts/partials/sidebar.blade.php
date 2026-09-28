@php
$teaItems=[];
try {
 $u=auth()->user();
 if($u && $u->can('tea_estate.access')) {
  foreach((array)config('teaestatemanagement_menu.items',[]) as $i) {
   if(\Illuminate\Support\Facades\Route::has($i['route']) && $u->can($i['key'])) $teaItems[]=$i;
  }
 }
} catch(\Throwable $e){$teaItems=[];}
@endphp
@if($teaItems)
<li class="nav-item {{ request()->segment(1)==='tea-estate-management'?'active active-sub':'' }}" data-sidebar-module="tea_estate_management">
 <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#tea-estate-menu" aria-expanded="{{ request()->segment(1)==='tea-estate-management'?'true':'false' }}"><i class="fa fa-leaf"></i><span>Tea Estate Management</span></a>
 <div id="tea-estate-menu" class="collapse {{ request()->segment(1)==='tea-estate-management'?'show':'' }}" data-parent="#accordionSidebar"><div class="py-2 collapse-inner rounded" style="background:transparent">
 @foreach($teaItems as $i)<a class="collapse-item" style="display:block;color:#fff!important;white-space:normal" href="{{ route($i['route']) }}"><i class="{{ $i['icon'] }} mr-1" style="color:#fff"></i> {{ $i['label'] }}</a>@endforeach
 </div></div>
</li>
@endif
