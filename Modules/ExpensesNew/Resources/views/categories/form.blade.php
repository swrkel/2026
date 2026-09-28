@extends('expensesnew::layouts.app', ['heading' => $category->id ? 'Edit Category' : 'Add Category'])

@section('module_content')

{{--
    MA-002 (S-620 #10): the same card design as the Expense Prefix settings -
    the 460px-style panel, the teal Save, the grey Close, matching label and
    input treatment.

    Scoped to .exn-cat-card so it cannot affect any other form.
--}}
<style>
    .exn-cat-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .15);
        padding: 22px 30px;
        margin: 10px auto 30px;
        max-width: 900px;
    }

    .exn-cat-card h2 {
        font-size: 18px;
        margin: 0 0 20px;
        color: #333;
        text-align: center;
        font-weight: 600;
    }

    .exn-cat-card .form-group { margin-bottom: 15px; }

    .exn-cat-card label {
        display: block;
        font-size: 14px;
        margin-bottom: 6px;
        color: #555;
        font-weight: 400;
    }

    .exn-cat-card .form-control,
    .exn-cat-card .select2-selection--single {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
        height: auto;
        box-shadow: none;
        transition: border-color .3s;
    }

    .exn-cat-card .form-control:focus {
        border-color: #009688;
        outline: none;
        box-shadow: none;
    }

    .exn-cat-card .help-block {
        font-size: 12px;
        color: #999;
        margin: 6px 0 0;
    }

    /* The flag checkboxes, in a row of their own. */
    .exn-cat-flags {
        margin-top: 6px;
        padding: 14px 16px;
        background: #f7f9fa;
        border: 1px solid #e3e8ea;
        border-radius: 8px;
    }

    .exn-cat-flags .checkbox { margin: 0; }

    .exn-cat-flags label {
        display: inline-block;
        font-size: 14px;
        color: #333;
        font-weight: 400;
        margin: 0;
        cursor: pointer;
    }

    .exn-cat-card .buttons {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .exn-cat-card .btn-exn {
        padding: 10px 18px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: background-color .3s;
    }

    .exn-cat-card .btn-save  { background-color: #009688; color: #fff; }
    .exn-cat-card .btn-save:hover  { background-color: #00796b; color: #fff; }
    .exn-cat-card .btn-close { background-color: #e0e0e0; color: #333; }
    .exn-cat-card .btn-close:hover { background-color: #c7c7c7; color: #333; }
</style>
@php
    $selectedDefaultPayeeId = old(
        'default_payee_id',
        $category->exists ? $category->default_payee_id : ($defaultPayeeId ?? null)
    );
@endphp
{{--
    LA-1147: this opening <form> tag was broken in half.

    The file read:

        <form method="post" action="{{ $category->
        <div class="exn-cat-card">
            <h2>{{ ... }}</h2>
        id ? route('expensesnew.categories.update', ...) : route(...) }}">

    The card <div> and its <h2> had been spliced INTO the middle of the
    `{{ $category->id ? ... }}` expression, between `$category->` and `id`. Blade
    compiled that into a PHP syntax error, so BOTH Add and Edit Category returned
    a page error before rendering anything. Add is simply the one that was
    reported.

    Restored: a complete <form> tag, then the card and heading. The card <div>
    still opens exactly once and still closes inside the form, so the surrounding
    markup is unchanged - div nesting was verified balanced at 19 opens / 19
    closes between here and </form>.
--}}
<form method="post" action="{{ $category->id ? route('expensesnew.categories.update', $category->id) : route('expensesnew.categories.store') }}">
<div class="exn-cat-card">
    <h2>{{ !empty($category->id) ? 'Edit Expense Category' : 'Add Expense Category' }}</h2>
    @csrf
    @if($category->id)
        @method('PUT')
    @endif

    <div class="box">
        <div class="box-body row">
            <div class="col-md-4">
                <label for="expnew_category_name">Name</label>
                <input id="expnew_category_name" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
            </div>

            <div class="col-md-4">
                <label for="expnew_category_code">Code</label>
                {{--
                    MA-002 (S-615 #4): the code loads automatically.

                    Filled from the prefix and starting number on the Settings
                    page, worked out from the highest code already in use.

                    STILL EDITABLE, deliberately. A category that genuinely
                    needs a hand-written code can have one, and the generator
                    ignores non-numeric codes when finding the next number, so
                    one exception does not break the sequence.

                    If the two settings have not been filled in yet the box
                    arrives empty and the help text says where to set them -
                    better than a silently blank field.
                --}}
                <input id="expnew_category_code"
                       name="code"
                       class="form-control"
                       autocomplete="off"
                       value="{{ old('code', $category->code ?: ($suggestedCode ?? '')) }}">
                {{-- LA-1147: guarded like line 148 above. edit() does not pass
                     $suggestedCode, so the bare reference here raised
                     "Undefined variable $suggestedCode" and broke Edit Category
                     even once the form tag was repaired. --}}
                @if(empty($category->code) && empty($suggestedCode ?? null))
                    <p class="help-block" style="margin-top:5px; font-size:12px; color:#94a3b8;">
                        Set a Prefix and Starting Number on the Settings page and codes will fill in here automatically.
                    </p>
                @elseif(empty($category->code))
                    <p class="help-block" style="margin-top:5px; font-size:12px; color:#94a3b8;">
                        Generated automatically. You can change it if this category needs a specific code.
                    </p>
                @endif
            </div>

            <div class="col-md-4">
                <label for="expnew_expense_account_id">Expense Account</label>
                <select id="expnew_expense_account_id" name="expense_account_id" class="form-control expnew-select2" required>
                    <option value="">Please select</option>
                    @foreach($accounts as $id => $name)
                        <option value="{{ $id }}" @selected((string) old('expense_account_id', $category->expense_account_id) === (string) $id)>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @if($accounts->isEmpty())
                    <small class="text-muted">No Finance List Account is currently classified under the Expense account type for this business.</small>
                @endif
            </div>

            <div class="col-md-4">
                <label for="expnew_default_payee_id">Default Payee Name</label>
                <select id="expnew_default_payee_id" name="default_payee_id" class="form-control expnew-select2">
                    <option value="">Please select</option>
                    @foreach($payees as $id => $name)
                        <option value="{{ $id }}" @selected((string) $selectedDefaultPayeeId === (string) $id)>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
                @if($payees->isEmpty())
                    <small class="text-muted">No supplier names are available for this business.</small>
                @endif
            </div>

            <div class="col-md-12">
                <label for="expnew_category_notes">Description</label>
                <textarea id="expnew_category_notes" name="description" class="form-control">{{ old('description', $category->description) }}</textarea>
            </div>

            <div class="col-md-3">
                <label>
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           @checked((bool) old('is_active', $category->exists ? $category->is_active : 1))>
                    Active
                </label>
            </div>
        </div>

            {{--
                MA-002 (S-612 #2): the three flags asked for.

                These need the columns added by
                    2026_08_06_000002_add_flags_to_expnew_categories.php
                Until that migration runs the boxes simply save nothing - the
                write filters unknown columns - so the form is safe to deploy
                first and migrate after.
            --}}
            <div class="col-md-12"><div class="exn-cat-flags"><div class="row">
            <div class="col-md-3">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="vat_input_claimed" value="0">
                        <input type="checkbox"
                               name="vat_input_claimed"
                               value="1"
                               @checked(old('vat_input_claimed', $category->vat_input_claimed ?? 0))>
                        VAT Input Claimed
                    </label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="is_sub_category" value="0">
                        <input type="checkbox"
                               name="is_sub_category"
                               value="1"
                               @checked(old('is_sub_category', $category->is_sub_category ?? 0))>
                        Sub Category
                    </label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="checkbox">
                    <label>
                        <input type="hidden" name="is_employee" value="0">
                        <input type="checkbox"
                               name="is_employee"
                               value="1"
                               @checked(old('is_employee', $category->is_employee ?? 0))>
                        Employee
                    </label>
                </div>
            </div>

            {{--
                IS1991 (#2): the selection each checkbox asks for.

                The boxes were saving their flag but there was nowhere to make
                the choice the flag implies, so ticking one appeared to do
                nothing.

                Both lists are rendered WITH THE PAGE, from $parentCategories and
                $employees, rather than fetched when the box is ticked. The lists
                are small and this is the failure the issue describes - a
                dropdown that opens empty because the request behind it did not
                arrive. Options that are already in the markup cannot do that.

                VAT Input Claimed has no field here on purpose: per the issue it
                controls whether the VAT Category field appears on Add Expenses,
                which categories/{id}/requirements already drives.
            --}}
            <div class="col-md-12 exn-flag-field" id="exn_parent_wrap" style="display:none;">
                <div class="form-group">
                    <label for="exn_parent_id">Parent Category:<span class="text-danger">*</span></label>
                    <select id="exn_parent_id" name="parent_id" class="form-control">
                        <option value="">Please Select</option>
                        @foreach($parentCategories ?? [] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('parent_id', $category->parent_id ?? '') === (string) $id)>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @if(empty($parentCategories) || count($parentCategories) === 0)
                        <p class="help-block">No other expense categories exist yet. Save one first and it will appear here.</p>
                    @endif
                </div>
            </div>

            <div class="col-md-12 exn-flag-field" id="exn_employee_wrap" style="display:none;">
                <div class="form-group">
                    <label for="exn_employee_id">Employees:<span class="text-danger">*</span></label>
                    <select id="exn_employee_id" name="employee_id" class="form-control">
                        <option value="">Please Select</option>
                        @foreach($employees ?? [] as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('employee_id', $category->employee_id ?? '') === (string) $id)>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                    @if(empty($employees) || count($employees) === 0)
                        <p class="help-block">No employees were found. Add them under HR Module &rarr; Employees &rarr; List Employees.</p>
                    @endif
                </div>
            </div>

        <div class="box-footer">
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
            </div></div></div>
    </div>
</div>
</form>

<script>
(function () {
    function initialise(attempt) {
        if (!window.jQuery) {
            if (attempt < 40) {
                window.setTimeout(function () { initialise(attempt + 1); }, 50);
            }
            return;
        }

        jQuery(function ($) {
        if (!$.fn.select2) {
            if (attempt < 40) {
                window.setTimeout(function () { initialise(attempt + 1); }, 50);
            }
            return;
        }

        $('.expnew-select2').each(function () {
            var $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) {
                $select.select2('destroy');
            }
            $select.select2({
                width: '100%',
                allowClear: !$select.prop('required'),
                placeholder: 'Please select',
                minimumResultsForSearch: 0
            });
        });

        /*
         * IS1991 (#2): show each dropdown while its checkbox is ticked.
         *
         * The options are already in the page, so this only reveals them - there
         * is no request to fail and nothing to wait for.
         *
         * `required` is applied only while the field is visible. A hidden select
         * carrying required blocks submission in every browser with a validation
         * message pointing at a control the user cannot see.
         */
        function exnBindFlagField(checkboxName, wrapId, selectId) {
            var $checkbox = $('input[type="checkbox"][name="' + checkboxName + '"]');
            var $wrap = $('#' + wrapId);
            var $select = $('#' + selectId);

            if (!$checkbox.length || !$wrap.length) {
                return;
            }

            function apply() {
                var on = $checkbox.is(':checked');
                $wrap.toggle(on);
                $select.prop('required', on);

                if (!on) {
                    $select.val('');
                }
            }

            $checkbox.on('change', apply);
            apply();
        }

        exnBindFlagField('is_sub_category', 'exn_parent_wrap', 'exn_parent_id');
        exnBindFlagField('is_employee', 'exn_employee_wrap', 'exn_employee_id');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { initialise(0); });
    } else {
        initialise(0);
    }
})();
</script>
@endsection
