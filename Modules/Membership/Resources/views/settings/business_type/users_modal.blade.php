<div class="modal fade" id="users_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">@lang('membership::lang.users_list')</h4>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>@lang('membership::lang.username')</th>
                            <th>@lang('membership::lang.full_name')</th>
                            <th>@lang('membership::lang.email')</th>
                        </tr>
                        </thead>
                        <tbody id="users_list_body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal" data-bs-dismiss="modal">@lang('messages.close')</button>
            </div>
        </div>
    </div>
</div>
