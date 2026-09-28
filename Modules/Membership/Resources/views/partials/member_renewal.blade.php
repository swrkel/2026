<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <form action="{{ action('\Modules\Membership\Http\Controllers\MembershipController@storeRenewal', $member->id) }}"
              method="POST" id="member_renewal_form">
            @csrf
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">Membership Renewal</h4>
            </div>

            <div class="modal-body">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><strong>{{ __('membership::lang.member_name') }}:</strong></label>
                            <p>{{ $member->member_name }}</p>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><strong>{{ __('membership::lang.member_number') }}:</strong></label>
                            <p>{{ $member->member_number }}</p>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            <label><strong>{{ __('membership::lang.region') }}:</strong></label>
                            <p>{{ $member->region ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('renewal_period', __('membership::lang.renewal_period').':*') !!}
                            {!! Form::select('renewal_period', [
                                'days' => __('membership::lang.days'),
                                'weeks' => __('membership::lang.weeks'),
                                'months' => __('membership::lang.months'),
                                'years' => __('membership::lang.years')
                            ], $member->renewal_period, ['class' => 'form-control', 'id' => 'member_renewal_period', 'placeholder' => __('membership::lang.please_select'), 'required']) !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('renewal_cycles', __('membership::lang.renewal_cycles').':*') !!}
                            {!! Form::number('renewal_cycles', $member->renewal_cycles, ['class' => 'form-control', 'id' => 'member_renewal_cycles', 'min' => 1, 'step' => 1, 'inputmode' => 'numeric', 'required']) !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('next_renewal_date_preview', __('membership::lang.renewal_date').':') !!}
                            {!! Form::text('next_renewal_date_preview', null, ['class' => 'form-control', 'id' => 'member_next_renewal_date_preview', 'readonly']) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('renewal_amount', __('membership::lang.registration_renewal_amount').':') !!}
                            {!! Form::number('renewal_amount', $member->registration_renewal_amount, ['class' => 'form-control', 'id' => 'member_renewal_amount', 'min' => 0, 'step' => 0.01]) !!}
                        </div>
                    </div>
                </div>

                <hr>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>{{ __('membership::lang.date_time') }}</th>
                                <th>{{ __('membership::lang.renewal_period') }}</th>
                                <th>{{ __('membership::lang.renewal_cycles') }}</th>
                                <th>{{ __('membership::lang.renewal_date') }}</th>
                                <th>{{ __('membership::lang.registration_renewal_amount') }}</th>
                                <th>User Renewed</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($renewals as $renewal)
                                <tr>
                                    <td>{{ $renewal->created_at ? $renewal->created_at->format('j M Y H:i') : '-' }}</td>
                                    <td>{{ ucfirst($renewal->renewal_period) }}</td>
                                    <td>{{ $renewal->renewal_cycles }}</td>
                                    <td>{{ $renewal->next_renewal_date ? \Carbon\Carbon::parse($renewal->next_renewal_date)->format('j M Y') : '-' }}</td>
                                    <td>{{ $renewal->renewal_amount !== null ? number_format($renewal->renewal_amount, 2) : '-' }}</td>
                                    <td>{{ optional($renewal->renewedBy)->username ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No renewal history found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
                <button type="submit" class="btn btn-primary" id="member_renewal_submit_btn">{{ __('messages.save') }}</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        function calculateMemberRenewalPreview() {
            var baseDate = @json($renewalBaseDate);
            var renewalPeriod = $('#member_renewal_period').val();
            var renewalCycles = $('#member_renewal_cycles').val();

            if (!baseDate || !renewalPeriod || !renewalCycles) {
                $('#member_next_renewal_date_preview').val('');
                return;
            }

            var baseMoment = moment(baseDate, ['YYYY-MM-DD', 'D MMM YYYY'], true);
            var cycles = parseInt(renewalCycles, 10);

            if (!baseMoment.isValid() || !cycles || cycles < 1) {
                $('#member_next_renewal_date_preview').val('');
                return;
            }

            var renewalMoment = baseMoment.clone();

            if (renewalPeriod === 'days') {
                renewalMoment.add(cycles, 'days');
            } else if (renewalPeriod === 'weeks') {
                renewalMoment.add(cycles, 'weeks');
            } else if (renewalPeriod === 'months') {
                renewalMoment.add(cycles, 'months');
            } else if (renewalPeriod === 'years') {
                renewalMoment.add(cycles, 'years');
            }

            $('#member_next_renewal_date_preview').val(renewalMoment.format('D MMM YYYY'));
        }

        $('#member_renewal_period').on('change', calculateMemberRenewalPreview);
        $('#member_renewal_cycles').on('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '');
            calculateMemberRenewalPreview();
        });

        calculateMemberRenewalPreview();
    })();
</script>
