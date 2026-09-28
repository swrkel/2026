<div class="row">
    <div class="col-xs-12">
        @can('add_membership_settings')
            <button type="button" class="btn btn-primary pull-right" id="membership_add_signature_btn">
                <i class="fa fa-plus"></i> @lang('messages.add') @lang('membership::lang.authorized_signature')
            </button>
        @endcan
    </div>
</div>
<br>
<div class="row">
    <div class="col-md-12">
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="membership_authorized_signatures_table" style="width:100%;">
                <thead>
                    <tr>
                        <th>@lang('messages.action')</th>
                        <th>@lang('membership::lang.date_time')</th>
                        <th>@lang('membership::lang.status')</th>
                        <th>@lang('membership::lang.signature')</th>
                        <th>@lang('membership::lang.added_by')</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade membership_authorized_signature_modal" tabindex="-1" role="dialog" aria-labelledby="membershipAuthorizedSignatureModalLabel">
    <div id="membership_authorized_signature_modal_content"></div>
</div>

@include('membership::settings.authorized_signature.create')
@include('membership::settings.authorized_signature.scripts')
