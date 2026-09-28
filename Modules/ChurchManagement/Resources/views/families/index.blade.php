@extends('churchmanagement::layouts.app', [
    'title' => __('churchmanagement::lang.families'),
    'heading' => __('churchmanagement::lang.families'),
    'subheading' => 'Households on the roll. A member can belong to one family.',
])

@section('chc_content')

@if(! $installed)
    @include('churchmanagement::partials.install_notice')
@endif

<div class="ch-card" id="chc-family-form-card">
    <div class="ch-card-header">
        <div>
            <h3 class="ch-card-title"><i class="fa fa-home text-primary"></i>
                <span id="chc-form-title">{{ __('churchmanagement::lang.add_family') }}</span></h3>
            <div class="ch-card-subtitle">
                The head of the family is chosen from members already on the roll, so create the
                people first if the household is new.
            </div>
        </div>
        <button type="button" class="btn btn-default btn-sm" id="chc-cancel-edit" style="display:none">
            <i class="fa fa-times"></i> {{ __('churchmanagement::lang.cancel') }}</button>
    </div>
    <div class="ch-card-body">
        <form method="post" id="chc-family-form" action="{{ route('churchmanagement.families.store') }}">
            @csrf
            <input type="hidden" name="_method" id="chc-form-method" value="POST">

            <div class="chc-form-grid">
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.family_name') }} <span style="color:#dc2626">*</span></label>
                    <input type="text" name="family_name" id="chc-family_name" maxlength="191"
                           value="{{ old('family_name') }}" required>
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.head_of_family') }}</label>
                    <select name="head_member_id" id="chc-head_member_id">
                        <option value="">— Not set —</option>
                        @foreach($memberOptions as $chcId => $chcName)
                            <option value="{{ $chcId }}" @selected((string) old('head_member_id') === (string) $chcId)>{{ $chcName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.phone') }}</label>
                    <input type="text" name="phone" id="chc-phone" maxlength="50" value="{{ old('phone') }}">
                </div>

                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.email') }}</label>
                    <input type="email" name="email" id="chc-email" maxlength="191" value="{{ old('email') }}">
                </div>
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.city') }}</label>
                    <input type="text" name="city" id="chc-city" maxlength="100" value="{{ old('city') }}">
                </div>
                <div class="chc-field">
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
            <h3 class="ch-card-title"><i class="fa fa-list text-primary"></i> {{ __('churchmanagement::lang.families') }}</h3>
            <div class="ch-card-subtitle">{{ $installed ? $families->total() : 0 }} household(s) for this business.</div>
        </div>
    </div>
    <div class="ch-card-body">

        <form method="get" class="ch-toolbar">
            <div class="chc-filters" style="flex:1">
                <div class="chc-field">
                    <label>{{ __('churchmanagement::lang.search') }}</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                           placeholder="Family name, code or phone">
                </div>
                <div class="chc-field" style="flex:0 0 auto;min-width:0">
                    <label>&nbsp;</label>
                    <div style="display:flex;gap:8px">
                        <button type="submit" class="btn btn-primary"><i class="fa fa-filter"></i> Apply</button>
                        <a href="{{ route('churchmanagement.families.index') }}" class="btn btn-default">
                            <i class="fa fa-refresh"></i> Reset</a>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="chc-table">
                <thead>
                    <tr>
                        <th>{{ __('churchmanagement::lang.family_code') }}</th>
                        <th>{{ __('churchmanagement::lang.family_name') }}</th>
                        <th>{{ __('churchmanagement::lang.head_of_family') }}</th>
                        <th>{{ __('churchmanagement::lang.phone') }}</th>
                        <th>{{ __('churchmanagement::lang.members_count') }}</th>
                        <th class="chc-actions-cell">{{ __('churchmanagement::lang.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($families as $chcRow)
                    <tr>
                        <td>{{ $chcRow->family_code }}</td>
                        <td>
                            <strong>{{ $chcRow->family_name }}</strong>
                            @if($chcRow->city)<div class="ch-page-note">{{ $chcRow->city }}</div>@endif
                        </td>
                        <td>{{ $memberOptions[$chcRow->head_member_id] ?? '—' }}</td>
                        <td>{{ $chcRow->phone }}</td>
                        <td>
                            <a href="{{ route('churchmanagement.members.index', ['family_id' => $chcRow->id]) }}">
                                <span class="ch-badge-soft info">{{ $chcRow->member_count ?? 0 }}</span>
                            </a>
                        </td>
                        <td class="chc-actions-cell">
                            <div class="chc-actions">
                                <button type="button" class="chc-btn-sm chc-edit" data-family='@json($chcRow)'>
                                    <i class="fa fa-pencil"></i> {{ __('churchmanagement::lang.edit') }}</button>
                                <form method="post" action="{{ route('churchmanagement.families.destroy', $chcRow->id) }}"
                                      onsubmit="return confirm('Remove this family? Its members stay on the roll, without a family.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="chc-btn-sm danger">
                                        <i class="fa fa-trash"></i> {{ __('churchmanagement::lang.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">
                        <div class="empty-state">{{ __('churchmanagement::lang.no_records') }}</div>
                    </td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($installed && $families->hasPages())
            <div style="margin-top:16px">{{ $families->links() }}</div>
        @endif

    </div>
</div>

@endsection

@section('chc_scripts')
<script>
(function () {
    'use strict';

    var form       = document.getElementById('chc-family-form');
    var methodEl   = document.getElementById('chc-form-method');
    var titleEl    = document.getElementById('chc-form-title');
    var submitEl   = document.getElementById('chc-submit-label');
    var cancelEl   = document.getElementById('chc-cancel-edit');
    var storeUrl   = @json(route('churchmanagement.families.store'));
    var updateBase = @json(url(config('churchmanagement.route_prefix', 'church-management') . '/families'));

    if (!form) { return; }

    var fields = ['family_name','head_member_id','phone','email','city','address','notes'];

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
                row = JSON.parse(button.getAttribute('data-family'));
            } catch (e) {
                return;
            }

            setValues(row);

            form.action = updateBase + '/' + row.id;
            methodEl.value = 'PUT';

            titleEl.textContent = 'Edit Family — ' + (row.family_code || row.family_name || '');
            submitEl.textContent = @json(__('churchmanagement::lang.update'));
            cancelEl.style.display = '';

            document.getElementById('chc-family-form-card').scrollIntoView({behavior: 'smooth', block: 'start'});
            document.getElementById('chc-family_name').focus();
        });
    });

    cancelEl.addEventListener('click', function () {
        setValues(null);
        form.action = storeUrl;
        methodEl.value = 'POST';
        titleEl.textContent = @json(__('churchmanagement::lang.add_family'));
        submitEl.textContent = @json(__('churchmanagement::lang.save'));
        cancelEl.style.display = 'none';
    });
})();
</script>
@endsection
