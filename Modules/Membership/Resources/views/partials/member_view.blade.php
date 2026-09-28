<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <h4 class="modal-title">{{ __('membership::lang.member_name') }}: {{ $member->member_name }}</h4>
        </div>

        <div class="modal-body">
            @php
                $raw = $member->other_mobile_numbers ?? '';
                $otherMobiles = $raw !== ''
                    ? array_filter(array_map('trim', preg_split('/\s*[\n\r]+|\s*<br\s*\/?>\s*/i', $raw, -1, PREG_SPLIT_NO_EMPTY)))
                    : [];
            @endphp

            {{-- Row 1 --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.date_time') }}:</strong></label>
                        <p>{{ $member->created_at ? $member->created_at->format('j M Y H:i') : '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.region') }}:</strong></label>
                        <p>{{ $member->region ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.date_joined') }}:</strong></label>
                        <p>{{ $member->date_joined ? \Carbon\Carbon::parse($member->date_joined)->format('j M Y') : '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.title') }}:</strong></label>
                        <p>{{ $member->title ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Row 2 --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.member_name') }}:</strong></label>
                        <p>{{ $member->member_name ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.member_name_other') }}:</strong></label>
                        <p>{{ $member->member_name_other ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.nic_no') }}:</strong></label>
                        <p>{{ $member->nic_no ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.gender') }}:</strong></label>
                        <p>{{ $member->gender ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Row 3 --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.date_of_birth') }}:</strong></label>
                        <p>{{ $member->date_of_birth ? \Carbon\Carbon::parse($member->date_of_birth)->format('j M Y') : '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.default_mobile_number') }}:</strong></label>
                        <p>{{ $member->default_mobile_number ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.other_mobile_numbers') }}:</strong></label>
                        @if(count($otherMobiles) > 0)
                            @foreach($otherMobiles as $num)
                                <div>{{ $num }}</div>
                            @endforeach
                        @else
                            <p>-</p>
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.member_address') }}:</strong></label>
                        <p>{{ $member->member_address ?? '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Row 4 --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.business_type') }}:</strong></label>
                        <p>{{ optional($member->businessType)->business_type ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.membership_type') }}:</strong></label>
                        <p>{{ optional($member->membershipType)->type_name ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.membership_status') }}:</strong></label>
                        <p>{{ optional($member->membershipStatus)->status_name ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.no_of_shares') }}:</strong></label>
                        <p>{{ $member->no_of_shares ?? 0 }}</p>
                    </div>
                </div>
            </div>

            {{-- Row 5 --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.total_share_value') }}:</strong></label>
                        <p>{{ number_format($member->total_share_value ?? 0, 2) }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.renewal_period') }}:</strong></label>
                        <p>{{ $member->renewal_period ? ucfirst($member->renewal_period) : '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.renewal_cycles') }}:</strong></label>
                        <p>{{ $member->renewal_cycles ?? '-' }}</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.renewal_date') }}:</strong></label>
                        <p>{{ $member->renewal_date ? \Carbon\Carbon::parse($member->renewal_date)->format('j M Y') : '-' }}</p>
                    </div>
                </div>
            </div>

            {{-- Row 6 --}}
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label><strong>{{ __('membership::lang.registration_renewal_amount') }}:</strong></label>
                        <p>{{ $member->registration_renewal_amount !== null ? number_format($member->registration_renewal_amount, 2) : '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('messages.close') }}</button>
        </div>
    </div>
</div>
