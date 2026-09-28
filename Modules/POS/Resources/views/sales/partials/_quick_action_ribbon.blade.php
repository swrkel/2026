<div class="pos-quick-ribbon">
    @foreach($quick_actions as $action)
        <button type="button" class="btn btn-default pos-quick-action" data-action="{{ $action['key'] }}">
            <i class="fa {{ $action['icon'] }}"></i> {{ $action['label'] }}
        </button>
    @endforeach
</div>
