@extends($layout)

@section('title', __('superadmin::lang.subscription'))

@section('content')

<!-- Main content -->
<section class="content">

	@include('superadmin::layouts.partials.currency')
@php
	$currency_symbol = App\Currency::where('id', $package->currency_id)->first();
    $is_my_auto_package = !empty($package->auto_services_and_repair_module) || !empty($package->home_dashboard);
@endphp
	<div class="box box-success">
        <div class="box-header">
            <h3 class="box-title">@lang('superadmin::lang.pay_and_subscribe')</h3>
        </div>

        <div class="box-body">
    		<div class="col-md-8">
        		<h3>
        			{{$package->name}}

        			(<span class="" >{{!empty($currency_symbol)? $currency_symbol->symbol : '' }} @if(!empty($custom_price)) {{number_format($custom_price,2)}} @else{{number_format($package->price, 2)}}@endif</span>

					<small>
						/ {{$package->interval_count}} {{ucfirst($package->interval)}}
					</small>)
        		</h3>
        		<ul>
					@if(!request()->session()->get('business.is_patient') && !$is_my_auto_package)
					<li>
						@if($package->location_count == 0)
							@lang('superadmin::lang.unlimited')
						@else
							@if(!empty($options['locations'])){{$options['locations']}} @else {{$package->location_count}} @endif
						@endif

						@lang('business.business_locations')
					</li>
					@endif

					@if($package->visitors_registration_module == 0)
					<li>
						@if($package->user_count == 0)
							@lang('superadmin::lang.unlimited')
						@else
						@if(!empty($options['users'])){{$options['users']}} @else {{$package->user_count}} @endif
						@endif
						@if(request()->session()->get('business.is_patient'))
						@lang('superadmin::lang.members')
						@else
						@lang('superadmin::lang.users')
						@endif
					</li>

					@if(!request()->session()->get('business.is_patient') && !$is_my_auto_package)
					<li>
						@if($package->product_count == 0)
							@lang('superadmin::lang.unlimited')
						@else
						@if(!empty($options['products'])){{$options['products']}} @else {{$package->product_count}} @endif
						@endif

						@lang('superadmin::lang.products')
					</li>
					@endif

					@if(!request()->session()->get('business.is_patient') && !$is_my_auto_package)
					<li>
						@if($package->invoice_count == 0)
							@lang('superadmin::lang.unlimited')
						@else
							{{$package->invoice_count}}
						@endif

						@lang('superadmin::lang.invoices')
					</li>
					@endif
					@endif

					@if($package->trial_days != 0)
						<li>
							{{$package->trial_days}} @lang('superadmin::lang.trial_days')
						</li>
					@endif
				</ul>

				@if(!empty($my_auto_checkout))
					<div class="panel panel-default">
						<div class="panel-heading clearfix">
							<strong style="float:left;">Payment Section</strong>
							<span style="float:right;">{{ $my_auto_number }}</span>
						</div>
						<div class="panel-body">
							<div class="form-group">
								<label for="my_auto_business_id_pay">Select My Auto</label>
								@if($my_auto_business_options->count() > 1)
									<select class="form-control js-my-auto-business-select2" id="my_auto_business_id_pay" style="width: 100%;">
										@foreach($my_auto_business_options as $business_id_option => $business_name_option)
											<option value="{{ $business_id_option }}" {{ (int) $business_id_option === (int) ($my_auto_checkout['business_id'] ?? 0) ? 'selected' : '' }}>
												{{ $business_name_option }}
											</option>
										@endforeach
									</select>
								@else
									@php
										$single_my_auto_business_id = $my_auto_business_options->keys()->first();
										$single_my_auto_business_name = $my_auto_business_options->first();
									@endphp
									<input
										type="text"
										class="form-control"
										id="my_auto_business_id_pay_label"
										value="{{ $single_my_auto_business_name }}"
										readonly
									>
									<input
										type="hidden"
										id="my_auto_business_id_pay"
										value="{{ $single_my_auto_business_id }}"
										data-auto-number="{{ $single_my_auto_business_name }}"
									>
									<p class="help-block" style="margin-bottom: 0;">Only one My Auto is available for this agent, so it has been selected automatically.</p>
								@endif
							</div>

							<div class="well well-sm" style="margin-bottom: 15px;">
								<div><strong>Selected My Auto:</strong> <span id="selected_my_auto_number_pay">{{ $my_auto_checkout['auto_number'] ?? '' }}</span></div>
								<div><strong>Package:</strong> <span id="selected_my_auto_package_pay">{{ $package->name }}</span></div>
								<div><strong>Subscription Cycle:</strong> <span id="selected_my_auto_subscription_cycle_pay">{{ $my_auto_checkout['subscription_cycle'] ?? '' }}</span></div>
								<div><strong>Amount to Auto load:</strong> <span id="selected_my_auto_amount_to_auto_load_pay">{{ ($my_auto_checkout['amount_to_auto_load'] ?? '') !== '' ? $my_auto_checkout['amount_to_auto_load'] : 'Not entered' }}</span></div>
							</div>

							<div style="margin-bottom: 15px;">
								<button type="button" class="btn btn-primary" id="show_my_auto_online_gateways">Online</button>
								<button type="button" class="btn btn-default" id="show_my_auto_offline_details">Offline</button>
							</div>

							<div id="my_auto_online_gateways_section" style="display:none;">
								@if(!empty($my_auto_online_gateways))
									@foreach($my_auto_online_gateways as $k => $v)
										<div class="list-group-item">
											<b>@lang('superadmin::lang.pay_via', ['method' => $v])</b>
											<div class="row" id="paymentdiv_{{$k}}">
												@php
													$view = 'superadmin::subscription.partials.pay_'.$k;
												@endphp
												@includeIf($view)
											</div>
										</div>
									@endforeach
								@else
									<div class="alert alert-warning" style="margin-bottom: 0;">
										No online payment gateways are active.
									</div>
								@endif
							</div>

							<div id="my_auto_offline_details_section" style="display:none;">
								<div class="list-group-item">
									<b>@lang('superadmin::lang.pay_via', ['method' => $my_auto_offline_gateway])</b>
									<div class="row" id="paymentdiv_offline">
										@includeIf('superadmin::subscription.partials.pay_offline', ['k' => 'offline', 'v' => $my_auto_offline_gateway])
									</div>
									<p class="help-block" style="margin-top: 10px;">Manual Activation is done by Super Admin.</p>
								</div>
							</div>
						</div>
					</div>
				@else
					<ul class="list-group">
						@foreach($gateways as $k => $v)
							<div class="list-group-item">
								<b>@lang('superadmin::lang.pay_via', ['method' => $v])</b>

								<div class="row" id="paymentdiv_{{$k}}">
									@php
										$view = 'superadmin::subscription.partials.pay_'.$k;
									@endphp
									@includeIf($view)
								</div>
							</div>
						@endforeach
					</ul>
				@endif
			</div>
        </div>
    </div>
</section>
@endsection

@section('javascript')
<script>
	@if(!empty($my_auto_checkout))
	@if($my_auto_business_options->count() > 1)
	$('#my_auto_business_id_pay').select2({
		width: '100%'
	});
	@endif

	window.getSelectedMyAutoCheckout = function () {
		var selectedElement = $('#my_auto_business_id_pay');
		var selectedOption = $('#my_auto_business_id_pay option:selected');
		var autoNumber = selectedOption.length
			? selectedOption.text()
			: (selectedElement.data('auto-number') || '');

		return {
			business_id: selectedElement.val(),
			auto_number: autoNumber,
			subscription_cycle: @json($my_auto_checkout['subscription_cycle'] ?? ''),
			amount_to_auto_load: @json($my_auto_checkout['amount_to_auto_load'] ?? '')
		};
	};

	function syncSelectedMyAutoDetails() {
		var details = window.getSelectedMyAutoCheckout();
		$('#selected_my_auto_number_pay').text(details.auto_number || '');
		$('#selected_my_auto_subscription_cycle_pay').text(details.subscription_cycle || '');
		$('#selected_my_auto_amount_to_auto_load_pay').text(details.amount_to_auto_load !== '' ? details.amount_to_auto_load : 'Not entered');
		$('.js-my-auto-business-id').val(details.business_id || '');
		$('.js-my-auto-subscription-cycle').val(details.subscription_cycle || '');
		$('.js-my-auto-amount-to-auto-load').val(details.amount_to_auto_load || '');
		$('.js-my-auto-number-label').text(details.auto_number || '');
		$('.js-my-auto-package-label').text($('#selected_my_auto_package_pay').text());
		$('.js-my-auto-cycle-label').text(details.subscription_cycle || '');
		$('.js-my-auto-auto-load-label').text(details.amount_to_auto_load !== '' ? details.amount_to_auto_load : 'Not entered');
	}

	$('#my_auto_business_id_pay').on('change', syncSelectedMyAutoDetails);
	syncSelectedMyAutoDetails();

	$('#show_my_auto_online_gateways').click(function () {
		$('#my_auto_offline_details_section').hide();
		$('#my_auto_online_gateways_section').show();
	});

	$('#show_my_auto_offline_details').click(function () {
		$('#my_auto_online_gateways_section').hide();
		$('#my_auto_offline_details_section').show();
	});
	@endif

	$('.btn-pay-online').click(function (e) {
		$('#pay_online_details_modal').modal('show');
	});
	$('.btn-confirm-online').click(function (e) {
		$('form.form-pay-online').submit();
	});

	$('.btn-pay-offline').click(function (e) {
		$('#pay_offline_details_modal').modal('show');
	});
</script>
@endsection
