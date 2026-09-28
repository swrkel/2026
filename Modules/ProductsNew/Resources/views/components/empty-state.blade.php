@props(['title'=>'No records found','message'=>'Try adjusting the filters or add a new record.','icon'=>'fa-inbox'])
<div class="pn-empty-state"><i class="fa {{ $icon }}"></i><strong>{{ $title }}</strong><p>{{ $message }}</p>{{ $slot }}</div>
