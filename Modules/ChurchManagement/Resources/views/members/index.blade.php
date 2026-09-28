@extends('churchmanagement::layouts.app', [
    'title' => __('churchmanagement::lang.members'),
    'heading' => __('churchmanagement::lang.members'),
    'subheading' => 'The congregation roll. Add, edit and search members, and link them to a family.',
])

@section('chc_content')

@if(! $installed)
    @include('churchmanagement::partials.install_notice')
@endif

{{--
    Add / Edit share ONE form.

    The Edit buttons below fill this form through a small script and switch it
    to the update route. Two separate forms would mean two sets of fields and
    two sets of validation rules to keep in step, and they always drift.
--}}
<div class="ch-card" id="chc-member-form-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-user-plus text-primary"></i>
                <span id="chc-form-title">{{ __('churchmanagement::lang.add_member') }}</span></h3>
            <div class="ch-card-subtitle">Only a first name is required. Everything else can be filled in later.</div>
        </div>
        <button type="button" class="btn btn-default btn-sm" id="chc-cancel-edit" style="display:none">
            <i class="fa fa-times"></i> {{ __('churchmanagement::lang.cancel') }}</button>
    </div>
    <div class="ch-card-body">
        <form method="post" id="chc-member-form"
              action="{{ route('churchmanagement.members.store') }}">
            @csrf
            <input type="hidden" name="_method" id="chc-form-method" value="POST">

            <div class="chc-form-grid">
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.first_name') }} <span style="color:#dc2626">*</span></label>
                    <input type="text" name="first_name" id="chc-first_name" maxlength="100"
                           value="{{ old('first_name') }}" required>
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.last_name') }}</label>
                    <input type="text" name="last_name" id="chc-last_name" maxlength="100"
                           value="{{ old('last_name') }}">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.membership_status') }}</label>
                    <select name="membership_status" id="chc-membership_status">
                        @foreach($statuses as $chcKey => $chcLabel)
                            <option value="{{ $chcKey }}" @selected(old('membership_status') === $chcKey)>{{ $chcLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.family') }}</label>
                    <select name="family_id" id="chc-family_id">
                        <option value="">— No family —</option>
                        @foreach($families as $chcId => $chcName)
                            <option value="{{ $chcId }}" @selected((string) old('family_id') === (string) $chcId)>{{ $chcName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.family_role') }}</label>
                    <input type="text" name="family_role" id="chc-family_role" maxlength="50"
                           value="{{ old('family_role') }}" placeholder="Head, Spouse, Child…">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.gender') }}</label>
                    <select name="gender" id="chc-gender">
                        <option value="">—</option>
                        @foreach($genders as $chcKey => $chcLabel)
                            <option value="{{ $chcKey }}" @selected(old('gender') === $chcKey)>{{ $chcLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.date_of_birth') }}</label>
                    <input type="date" name="date_of_birth" id="chc-date_of_birth" value="{{ old('date_of_birth') }}">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.marital_status') }}</label>
                    <select name="marital_status" id="chc-marital_status">
                        <option value="">—</option>
                        @foreach($maritals as $chcKey => $chcLabel)
                            <option value="{{ $chcKey }}" @selected(old('marital_status') === $chcKey)>{{ $chcLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.occupation') }}</label>
                    <input type="text" name="occupation" id="chc-occupation" maxlength="191" value="{{ old('occupation') }}">
                </div>

                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.phone') }}</label>
                    <input type="text" name="phone" id="chc-phone" maxlength="50" value="{{ old('phone') }}">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.whatsapp') }}</label>
                    <input type="text" name="whatsapp" id="chc-whatsapp" maxlength="50" value="{{ old('whatsapp') }}">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.email') }}</label>
                    <input type="email" name="email" id="chc-email" maxlength="191" value="{{ old('email') }}">
                </div>

                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.joined_date') }}</label>
                    <input type="date" name="joined_date" id="chc-joined_date" value="{{ old('joined_date') }}">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.baptism_date') }}</label>
                    <input type="date" name="baptism_date" id="chc-baptism_date" value="{{ old('baptism_date') }}">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.confirmation_date') }}</label>
                    <input type="date" name="confirmation_date" id="chc-confirmation_date" value="{{ old('confirmation_date') }}">
                </div>

                @if($locations->count())
                    {{--
                        Only rendered where the business actually has locations.
                        A single-site congregation should not be asked to choose
                        between one option.
                    --}}
                    <div class="chc-field">
                        <label>Location</label>
                        <select name="business_location_id" id="chc-business_location_id">
                            <option value="">— Not set —</option>
                            @foreach($locations as $chcLocId => $chcLocName)
                                <option value="{{ $chcLocId }}" @selected((string) old('business_location_id') === (string) $chcLocId)>{{ $chcLocName }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.city') }}</label>
                    <input type="text" name="city" id="chc-city" maxlength="100" value="{{ old('city') }}">
                </div>
                <div class="chc-field" style="grid-column:span 2">
                    <label>{{ __('churchmanagement::lang.address') }}</label>
                    <input type="text" name="address" id="chc-address" maxlength="1000" value="{{ old('address') }}">
                </div>

                <div class="chc-field" style="grid-column:1 / -1">
                    <label>{{ __('churchmanagement::lang.notes') }}</label>
                    <textarea name="notes" id="chc-notes" maxlength="2000">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="chc-form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-save"></i> <span id="chc-submit-label">{{ __('churchmanagement::lang.save') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div class="ch-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-list text-primary"></i> {{ __('churchmanagement::lang.members') }}</h3>
            <div class="ch-card-subtitle">
                {{ $installed ? $members->total() : 0 }} record(s) for this business.
            </div>
        </div>
    </div>
    <div class="ch-card-body">

        <form method="get" class="ch-toolbar">
            <div class="chc-filters" style="flex:1">
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.search') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Name, code, phone or email">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.membership_status') }}</label>
                    <select name="status">
                        <option value="">All</option>
                        @foreach($statuses as $chcKey => $chcLabel)
                            <option value="{{ $chcKey }}" @selected(($filters['status'] ?? '') === $chcKey)>{{ $chcLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.family') }}</label>
                    <select name="family_id">
                        <option value="">All</option>
                        @foreach($families as $chcId => $chcName)
                            <option value="{{ $chcId }}" @selected((string) ($filters['family_id'] ?? '') === (string) $chcId)>{{ $chcName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chc-field" style="flex:0 0 auto;min-width:0">
                    <label>&nbsp;</label>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button>
                        <a href="{{ route('churchmanagement.members.index') }}" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Reset</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="chc-table">
                <thead>
                    <tr>
                        <th>{{ __('churchmanagement::lang.member_code') }}</th>
                        <th>{{ __('churchmanagement::lang.full_name') }}</th>
                        <th>{{ __('churchmanagement::lang.family') }}</th>
                        <th>{{ __('churchmanagement::lang.phone') }}</th>
                        <th>{{ __('churchmanagement::lang.membership_status') }}</th>
                        <th>Added By</th>
                        <th class="chc-actions-cell">{{ __('churchmanagement::lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($members as $chcRow)
                    <tr>
                        <td>{{ $chcRow->member_code }}</td>
                        <td>
                            <strong>{{ $chcRow->full_name }}</strong>
                            @if($chcRow->email)<div class="ch-page-note">{{ $chcRow->email }}</div>@endif
                        </td>
                        <td>{{ $families[$chcRow->family_id] ?? '—' }}</td>
                        <td>{{ $chcRow->phone }}</td>
                        <td><span class="chc-pill {{ $chcRow->membership_status }}">
                            {{ $statuses[$chcRow->membership_status] ?? ucfirst($chcRow->membership_status) }}</span></td>
                        <td>{{ $creators[$chcRow->created_by] ?? '—' }}</td>
                        <td class="chc-actions-cell">
                            <div class="chc-actions">
                                {{--
                                    The row is handed to the edit script as JSON on the button
                                    itself. No second request is needed to open a record for
                                    editing, and the data shown is exactly the data listed.
                                --}}
                                <button type="button" class="chc-btn-sm chc-edit"
                                        data-member='@json($chcRow)'>
                                    <i class="fa fa-pencil"></i> {{ __('churchmanagement::lang.edit') }}</button>
                                <form method="post" action="{{ route('churchmanagement.members.destroy', $chcRow->id) }}"
                                      onsubmit="return confirm('Remove this member from the roll?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="chc-btn-sm danger">
                                        <i class="fa fa-trash"></i> {{ __('churchmanagement::lang.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">
                        <div class="empty-state">{{ __('churchmanagement::lang.no_records') }}</div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($installed && $members->hasPages())
            <div style="margin-top:16px">{{ $members->links() }}</div>
        @endif

    </div>
</div>

@endsection

@section('chc_scripts')
<script>
(function () {
    'use strict';

    var form       = document.getElementById('chc-member-form');
    var methodEl   = document.getElementById('chc-form-method');
    var titleEl    = document.getElementById('chc-form-title');
    var submitEl   = document.getElementById('chc-submit-label');
    var cancelEl   = document.getElementById('chc-cancel-edit');
    var storeUrl   = @json(route('churchmanagement.members.store'));
    var updateBase = @json(url(config('churchmanagement.route_prefix', 'church-management') . '/members'));

    if (!form) { return; }

    /* Fields the edit button fills. Listed once so adding a field to the form
       means adding it here, rather than hunting through the script. */
    /* Every field the edit button fills. A field added to the form must be
       added here too, or editing would silently leave it at the previous
       record's value. */
    var fields = ['first_name','last_name','membership_status','family_id','family_role',
                  'gender','date_of_birth','marital_status','occupation','phone','whatsapp',
                  'email','joined_date','baptism_date','confirmation_date',
                  'business_location_id','city','address','notes'];

    function setValues(row) {
        fields.forEach(function (name) {
            var el = document.getElementById('chc-' + name);
            if (!el) { return; }
            var value = row ? row[name] : '';
            el.value = (value === null || typeof value === 'undefined') ? '' : value;
        });
    }

    document.querySelectorAll('.chc-edit').forEach(function (button) {
        button.addEventListener('click', function () {
            var row;
            try {
                row = JSON.parse(button.getAttribute('data-member'));
            } catch (e) {
                return;
            }

            setValues(row);

            /* Laravel reads _method for PUT, so the same form posts to the
               update route without the browser needing to support PUT. */
            form.action = updateBase + '/' + row.id;
            methodEl.value = 'PUT';

            titleEl.textContent = 'Edit Member — ' + (row.member_code || row.full_name || '');
            submitEl.textContent = @json(__('churchmanagement::lang.update'));
            cancelEl.style.display = '';

            document.getElementById('chc-member-form-card').scrollIntoView({behavior: 'smooth', block: 'start'});
            document.getElementById('chc-first_name').focus();
        });
    });

    cancelEl.addEventListener('click', function () {
        setValues(null);
        form.action = storeUrl;
        methodEl.value = 'POST';
        titleEl.textContent = @json(__('churchmanagement::lang.add_member'));
        submitEl.textContent = @json(__('churchmanagement::lang.save'));
        cancelEl.style.display = 'none';
        document.getElementById('chc-membership_status').value = 'member';
    });
})();
</script>
@endsection
