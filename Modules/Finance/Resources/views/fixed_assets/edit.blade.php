<div class="modal-dialog modal-lg" role="document"><div class="modal-content">
<div class="modal-header"><button type="button" class="close" data-dismiss="modal"><span>&times;</span></button><h4 class="modal-title">@lang('messages.edit') @lang('account.fixed_assets')</h4></div>
<div class="modal-body">
@php($dateValue = optional($fixed_asset->date_of_operation)->format('Y-m-d\TH:i'))
@php($accountValue = old('account_id', $fixed_asset->account_id))
@php($nameValue = old('asset_name', $fixed_asset->asset_name))
@php($locationValue = old('asset_location', $fixed_asset->asset_location))
@php($amountValue = old('amount', $fixed_asset->amount))
<form method="POST" action="{{ route('finance.fixed-assets.update', $fixed_asset->id) }}">@csrf @method('PUT')

<div class="row">
    <div class="col-md-4"><div class="form-group"><label>@lang('account.date')</label><input type="datetime-local" class="form-control" name="date_of_operation" value="{{ $dateValue }}" required></div></div>
    <div class="col-md-4"><div class="form-group"><label>@lang('account.account'):</label><select name="account_id" class="form-control select2" required style="width:100%"><option value="">@lang('messages.please_select')</option>@foreach($accounts as $id => $name)<option value="{{ $id }}" {{ (string)$accountValue === (string)$id ? 'selected' : '' }}>{{ $name }}</option>@endforeach</select></div></div>
    <div class="col-md-4"><div class="form-group"><label>@lang('account.asset_name')</label><input type="text" class="form-control" name="asset_name" value="{{ $nameValue }}" required></div></div>
    <div class="col-md-4"><div class="form-group"><label>@lang('account.asset_location')</label><input type="text" class="form-control" name="asset_location" value="{{ $locationValue }}" required></div></div>
    <div class="col-md-4"><div class="form-group"><label>@lang('account.amount')</label><input type="number" step="0.0001" min="0" class="form-control" name="amount" value="{{ $amountValue }}" required></div></div>
</div>

<button type="submit" class="btn btn-primary">@lang('messages.update')</button> <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
</form></div></div></div><script>$('.select2').select2();</script>
