{{--
    IS1991 (#1): the Prefix List.

    Every prefix saved on the card above appears here with who created it and
    the date and time it was saved.

    Edit and Delete are switched off for a prefix that already has expense
    transactions behind it. That is counted live from the expenses themselves
    (see ExpensePrefixUsageService), so removing the last of those expenses
    through List Expenses > Action > Delete brings both options back by itself -
    there is no flag to reset.

    The prefix marked "In use" is the one CategoryCodeGenerator reads when it
    builds the next category code.
--}}
<style>
    .exn-prefix-list {
        max-width: 900px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, .15);
        padding: 20px 26px 26px;
        margin: 10px auto 30px;
    }

    .exn-prefix-list h2 {
        font-size: 18px;
        margin: 0 0 16px;
        color: #333;
        text-align: center;
        font-weight: 600;
    }

    .exn-prefix-list table { width: 100%; margin: 0; }

    .exn-prefix-list thead th {
        font-size: 13px;
        font-weight: 600;
        color: #555;
        background: #f7f9fa;
        border-bottom: 1px solid #e3e8ea !important;
        white-space: nowrap;
    }

    .exn-prefix-list tbody td {
        font-size: 13px;
        color: #333;
        vertical-align: middle !important;
    }

    .exn-prefix-badge {
        display: inline-block;
        margin-left: 6px;
        padding: 1px 7px;
        border-radius: 10px;
        background: #e0f2f1;
        border: 1px solid #b2dfdb;
        color: #00695c;
        font-size: 11px;
        font-weight: 600;
    }

    .exn-prefix-list .btn-xs { margin-right: 4px; }

    .exn-prefix-list .exn-locked-note {
        display: block;
        margin-top: 4px;
        font-size: 11px;
        color: #999;
    }

    .exn-prefix-edit-row > td {
        background: #f7f9fa !important;
        border-top: none !important;
    }

    .exn-prefix-edit-row .form-control {
        display: inline-block;
        width: auto;
        min-width: 130px;
        margin-right: 8px;
        height: 34px;
    }

    .exn-prefix-empty {
        text-align: center;
        color: #999;
        font-size: 13px;
        padding: 18px 0 !important;
    }
</style>

<div class="exn-prefix-list">
    <h2>Prefix List</h2>

    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Prefix</th>
                    <th>Starting No.</th>
                    <th>Created User Name</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th style="width:170px;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prefixes as $prefix)
                    @php
                        $locked = (bool) ($prefix->has_transactions ?? false);
                        $usageCount = (int) ($prefix->transaction_count ?? 0);
                        $isActive = (string) $prefix->prefix === (string) ($activePrefix ?? '');
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $prefix->prefix }}</strong>
                            @if($isActive)
                                <span class="exn-prefix-badge" title="Used when generating the next category code.">In use</span>
                            @endif
                        </td>
                        <td>{{ $prefix->starting_no !== null && $prefix->starting_no !== '' ? $prefix->starting_no : '—' }}</td>
                        <td>{{ $prefix->created_user_name ?? '—' }}</td>
                        <td>{{ optional($prefix->created_at)->format('d/m/Y') ?: '—' }}</td>
                        <td>{{ optional($prefix->created_at)->format('H:i') ?: '—' }}</td>
                        <td>
                            @if($locked)
                                <button type="button" class="btn btn-xs btn-default" disabled aria-disabled="true"
                                        title="This prefix is used by {{ $usageCount }} expense transaction{{ $usageCount === 1 ? '' : 's' }}.">
                                    Edit
                                </button>
                                <button type="button" class="btn btn-xs btn-danger" disabled aria-disabled="true"
                                        title="This prefix is used by {{ $usageCount }} expense transaction{{ $usageCount === 1 ? '' : 's' }}.">
                                    Delete
                                </button>
                                <small class="exn-locked-note">
                                    Used by {{ $usageCount }} expense transaction{{ $usageCount === 1 ? '' : 's' }} — create a new prefix instead.
                                </small>
                            @else
                                <button type="button"
                                        class="btn btn-xs btn-primary exn-prefix-edit-toggle"
                                        data-target="exn-prefix-edit-{{ $prefix->id }}">
                                    Edit
                                </button>

                                <form method="post"
                                      action="{{ route('expensesnew.settings.prefixes.destroy', $prefix->id) }}"
                                      style="display:inline;"
                                      onsubmit="return confirm('Delete the prefix {{ $prefix->prefix }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-danger">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>

                    @unless($locked)
                        <tr class="exn-prefix-edit-row" id="exn-prefix-edit-{{ $prefix->id }}" style="display:none;">
                            <td colspan="6">
                                <form method="post" action="{{ route('expensesnew.settings.prefixes.update', $prefix->id) }}">
                                    @csrf
                                    @method('PUT')
                                    <label style="font-weight:400; margin-right:6px;">Prefix</label>
                                    <input type="text"
                                           name="prefix"
                                           class="form-control"
                                           maxlength="20"
                                           required
                                           value="{{ $prefix->prefix }}">

                                    <label style="font-weight:400; margin-right:6px;">Starting No.</label>
                                    <input type="text"
                                           name="starting_no"
                                           class="form-control"
                                           maxlength="12"
                                           value="{{ $prefix->starting_no }}">

                                    <button type="submit" class="btn btn-xs btn-primary">Save</button>
                                    <button type="button"
                                            class="btn btn-xs btn-default exn-prefix-edit-cancel"
                                            data-target="exn-prefix-edit-{{ $prefix->id }}">
                                        Cancel
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endunless
                @empty
                    <tr>
                        <td colspan="6" class="exn-prefix-empty">
                            No prefixes saved yet. Enter a prefix above and press Save.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
    /*
     * IS1991: reveal the inline edit row.
     *
     * Plain form posts rather than AJAX - the row that comes back is rendered
     * by the same code as the rest of the list, so an edited prefix can never
     * disagree with what the server holds.
     */
    (function () {
        function row(id) {
            return document.getElementById(id);
        }

        document.addEventListener('click', function (event) {
            var toggle = event.target.closest('.exn-prefix-edit-toggle');
            if (toggle) {
                var target = row(toggle.getAttribute('data-target'));
                if (target) {
                    target.style.display = target.style.display === 'table-row' ? 'none' : 'table-row';
                    var input = target.querySelector('input[name="prefix"]');
                    if (input && target.style.display === 'table-row') {
                        input.focus();
                    }
                }
                return;
            }

            var cancel = event.target.closest('.exn-prefix-edit-cancel');
            if (cancel) {
                var cancelTarget = row(cancel.getAttribute('data-target'));
                if (cancelTarget) {
                    cancelTarget.style.display = 'none';
                }
            }
        });
    }());
</script>
