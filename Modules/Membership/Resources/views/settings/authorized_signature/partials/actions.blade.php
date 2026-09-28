<div class="btn-group">
    <button type="button" class="btn btn-info btn-xs membership-signature-load-modal"
            data-href="{{ route('membership.setting.authorized-signatures.show', $row->id) }}">
        <i class="glyphicon glyphicon-eye-open"></i> @lang('messages.view')
    </button>

    @can('edit_membership_settings')
        <button type="button" class="btn btn-primary btn-xs membership-signature-load-modal"
                data-href="{{ route('membership.setting.authorized-signatures.edit', $row->id) }}">
            <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
        </button>
        <button type="button" class="btn btn-danger btn-xs membership-signature-delete"
                data-href="{{ route('membership.setting.authorized-signatures.destroy', $row->id) }}">
            <i class="fa fa-trash"></i> @lang('messages.delete')
        </button>
    @endcan
</div>
