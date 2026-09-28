<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <h4 class="modal-title">@lang('membership::lang.view_point_activity')</h4>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.add_point_form_no'):</label>
                        <p>{{ $point->form_number ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.date'):</label>
                        <p>{{ $point->date ? @format_date($point->date) : '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.member_name'):</label>
                        <p>
                            @if($member)
                                @if(isset($member->member_name))
                                    {{ $member->member_name }}
                                @elseif(isset($member->name))
                                    {{ $member->name }}
                                @else
                                    -
                                @endif
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.member_code'):</label>
                        <p>
                            @if($member)
                                @if(isset($member->member_number))
                                    {{ $member->member_number }}
                                @elseif(isset($member->contact_id))
                                    {{ $member->contact_id }}
                                @elseif(isset($member->id))
                                    {{ $member->id }}
                                @else
                                    -
                                @endif
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.business_type'):</label>
                        <p>{{ $businessType ? $businessType->business_type : '-' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.business_name'):</label>
                        <p>{{ $businessName ?? '-' }}</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.bill_number'):</label>
                        <p>{{ $point->bill_number ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.amount'):</label>
                        <p>{{ @num_format($point->amount ?? 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('membership::lang.current_points'):</label>
                        <p>{{ @num_format(($point->point_balance ?? 0) - ($point->earned_points ?? 0) + ($point->redeemed_points ?? 0)) }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('membership::lang.earned_points'):</label>
                        <p>{{ @num_format($point->earned_points ?? 0) }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('membership::lang.redeemed_points'):</label>
                        <p>{{ @num_format($point->redeemed_points ?? 0) }}</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.point_balance'):</label>
                        <p>{{ @num_format($point->point_balance ?? 0) }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.payment_details'):</label>
                        <p>
                            @if($point->payment_details)
                                @php
                                    $paymentDetails = is_string($point->payment_details) ? json_decode($point->payment_details, true) : $point->payment_details;
                                    if (is_array($paymentDetails)) {
                                        echo implode(', ', $paymentDetails);
                                    } else {
                                        echo $point->payment_details;
                                    }
                                @endphp
                            @else
                                -
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            @if($point->bank_name || $point->cheque_number || $point->cheque_date)
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('contact.bank_name'):</label>
                        <p>{{ $point->bank_name ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('contact.cheque_number'):</label>
                        <p>{{ $point->cheque_number ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>@lang('contact.cheque_date'):</label>
                        <p>{{ $point->cheque_date ? @format_date($point->cheque_date) : '-' }}</p>
                    </div>
                </div>
            </div>
            @endif

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('membership::lang.added_by'):</label>
                        <p>{{ $createdBy ? $createdBy->username : '-' }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>@lang('messages.created_at'):</label>
                        <p>{{ $point->created_at ? @format_datetime($point->created_at) : '-' }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>
    </div>
</div>

