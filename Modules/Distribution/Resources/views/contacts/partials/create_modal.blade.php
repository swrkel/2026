{{--
    Distribution-owned quick customer form placeholder.
    Keeps modal ownership inside Distribution. The save endpoint can be wired to a Distribution customer controller in the customer separation stage.
--}}
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('contact.add_customer')</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-info" style="margin-bottom:0;">
                    @lang('distribution::lang.customer_quick_create_owned_by_distribution')
                </div>
            </div>
        </div>
    </div>
</div>
