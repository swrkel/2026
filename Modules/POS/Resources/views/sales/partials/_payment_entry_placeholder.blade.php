<div class="modal fade" id="pos_payment_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header pos-modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-credit-card"></i> {{ __('pos::page_003.enterprise_payment_entry') }}</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">{{ __('pos::page_003.payment_workspace_notice') }}</div>
                <table class="table table-bordered" id="pos_payment_lines_table">
                    <thead><tr><th>{{ __('pos::page_003.payment_method') }}</th><th class="text-right">{{ __('pos::page_003.amount') }}</th><th>{{ __('pos::page_003.reference') }}</th><th></th></tr></thead>
                    <tbody>
                        <tr>
                            <td><select class="form-control pos-payment-method">@foreach($payment_methods as $method)<option value="{{ $method['name'] }}">{{ $method['name'] }}</option>@endforeach</select></td>
                            <td><input type="text" class="form-control text-right pos-payment-amount" value="0.0000"></td>
                            <td><input type="text" class="form-control pos-payment-reference"></td>
                            <td><button type="button" class="btn btn-danger btn-sm pos-remove-payment-line"><i class="fa fa-times"></i></button></td>
                        </tr>
                    </tbody>
                </table>
                <button type="button" class="btn btn-primary" id="pos_add_payment_line"><i class="fa fa-plus"></i> {{ __('pos::page_003.add_payment_line') }}</button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('pos::page_003.close') }}</button>
                <button type="button" class="btn btn-success">{{ __('pos::page_003.complete_payment') }}</button>
            </div>
        </div>
    </div>
</div>
