@props(['id', 'title' => '', 'size' => 'modal-lg'])
<div class="modal fade erp-modal" id="{{ $id }}" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog {{ $size }}" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title erp-modal-title">{{ $title }}</h4>
            </div>
            {{ $slot }}
        </div>
    </div>
</div>
