<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-membership-dismiss="modal" data-membership-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">@lang('membership::lang.view_business_name')</h4>
        </div>

        <div class="modal-body">
            <table class="table table-bordered">
                <tr>
                    <th width="30%">@lang('membership::lang.business_type')</th>
                    <td>{{ optional($businessName->businessType)->business_type ?? '-' }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.Business_name')</th>
                    <td>{{ $businessName->business_name }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.added_by')</th>
                    <td>{{ optional($businessName->createdBy)->username ?? '-' }}</td>
                </tr>
                <tr>
                    <th>@lang('membership::lang.date_time')</th>
                    <td>{{ $businessName->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            </table>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-membership-dismiss="modal" data-membership-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

