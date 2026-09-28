@php
    $offline_bank_name = \App\System::getProperty('PAY_ONLINE_BANK_NAME') ?: env('PAY_ONLINE_BANK_NAME');
    $offline_branch_name = \App\System::getProperty('PAY_ONLINE_BRANCH_NAME') ?: env('PAY_ONLINE_BRANCH_NAME');
    $offline_account_no = \App\System::getProperty('PAY_ONLINE_ACCOUNT_NO') ?: env('PAY_ONLINE_ACCOUNT_NO');
    $offline_account_name = \App\System::getProperty('PAY_ONLINE_ACCOUNT_NAME') ?: env('PAY_ONLINE_ACCOUNT_NAME');
    $offline_swift_code = \App\System::getProperty('PAY_ONLINE_SWIFT_CODE') ?: env('PAY_ONLINE_SWIFT_CODE');
@endphp
<div class="col-md-12">
	<form class="form-pay-offline" action="{{action('\Modules\Superadmin\Http\Controllers\SubscriptionController@confirm', [$package->id])}}" method="POST">
	 	{{ csrf_field() }}
	 	<input type="hidden" name="gateway" value="{{$k}}">
	 	<input type="hidden" name="custom_price" value="@if(!empty($custom_price)){{$custom_price}}@else{{$package->price}}@endif">
	 	<input type="hidden" name="option_variables_selected" value="@if(!empty($option_variables_selected)){{$option_variables_selected}}@endif">
	 	<input type="hidden" name="module_selected" value="@if(!empty($module_selected)){{$module_selected}}@endif">
	 	@if(!empty($my_auto_checkout))
	 	<input type="hidden" name="my_auto_business_id" class="js-my-auto-business-id" value="{{ $my_auto_checkout['business_id'] ?? '' }}">
	 	<input type="hidden" name="my_auto_subscription_cycle" class="js-my-auto-subscription-cycle" value="{{ $my_auto_checkout['subscription_cycle'] ?? '' }}">
	 	<input type="hidden" name="my_auto_amount_to_auto_load" class="js-my-auto-amount-to-auto-load" value="{{ $my_auto_checkout['amount_to_auto_load'] ?? '' }}">
	 	@endif

	 	<button type="button" class="btn btn-success btn-pay-offline"> <i class="fa fa-hand-grab-o"></i> {{$v}}</button>
	</form>
	<p class="help-block">@lang('superadmin::lang.offline_pay_helptext')</p>

	<div class="modal" tabindex="-1" role="dialog" id="pay_offline_details_modal">
	    <div class="modal-dialog" role="document" style="width: 40% !important;">
	            <div class="modal-content">
	            <div class="modal-body text-center">
					<table class="table table-condensed">
				        <tbody>
				        	@if(!empty($my_auto_checkout))
				        		<tr>
				        			<td>
				        				<strong>Select My Auto</strong>
				        			</td>
				        			<td class="js-my-auto-number-label">
				        				{{ $my_auto_checkout['auto_number'] ?? '' }}
				        			</td>
				        		</tr>
				        		<tr>
				        			<td>
				        				<strong>Package</strong>
				        			</td>
				        			<td class="js-my-auto-package-label">
				        				{{ $package->name }}
				        			</td>
				        		</tr>
				        		<tr>
				        			<td>
				        				<strong>Subscription Cycle</strong>
				        			</td>
				        			<td class="js-my-auto-cycle-label">
				        				{{ $my_auto_checkout['subscription_cycle'] ?? '' }}
				        			</td>
				        		</tr>
				        		<tr>
				        			<td>
				        				<strong>Amount to Auto load</strong>
				        			</td>
				        			<td class="js-my-auto-auto-load-label">
				        				{{ ($my_auto_checkout['amount_to_auto_load'] ?? '') !== '' ? $my_auto_checkout['amount_to_auto_load'] : 'Not entered' }}
				        			</td>
				        		</tr>
				        	@endif
				            <tr>
				                <td>
				                    <strong>@lang('superadmin::lang.bank_name')</strong>
				                </td>
				                <td>
				                    {{$offline_bank_name}}
				                </td>
				            </tr>
				            <tr>
				                <td>
				                    <strong>@lang('superadmin::lang.branch_name')</strong>
				                </td>
				                <td>
				                    {{$offline_branch_name}}
				                </td>
				            </tr>
				            <tr>
				                <td>
				                    <strong>@lang('superadmin::lang.account_no')</strong>
				                </td>
				                <td>
				                    {{$offline_account_no}}
				                </td>
				            </tr>
				            <tr>
				                <td>
				                    <strong>@lang('superadmin::lang.account_name')</strong>
				                </td>
				                <td>
				                    {{$offline_account_name}}
				                </td>
				            </tr>
				            <tr>
				                <td>
				                    <strong>@lang('superadmin::lang.swift_code')</strong>
				                </td>
				                <td>
				                    {{$offline_swift_code}}
				                </td>
				            </tr>
				        </tbody>
				    </table>
	            </div>
	            <div class="modal-footer">
	                <button type="button" class="btn btn-secondary btn-confirm-ok" data-dismiss="modal">Ok</button>
	            </div>
	        </div>
	    </div>
	</div>
</div>
