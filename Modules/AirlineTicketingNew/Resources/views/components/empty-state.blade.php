@props(['title'=>'No records','message'=>null])
<div class="atn-empty-state">
    <i class="fa fa-inbox"></i>
    <h4>{{ $title }}</h4>
    @if($message)<p>{{ $message }}</p>@endif
</div>
