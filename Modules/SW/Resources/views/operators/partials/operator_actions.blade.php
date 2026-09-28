{{--
    Row actions for the Pump Operators table.

    Recover Shortage and Pay Excess are offered only when there is something to
    recover or pay. Showing them against a zero balance invites an entry that
    posts to the ledger for no reason.
--}}
<div class="btn-group">
    <button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown"
        aria-expanded="false">@lang('messages.actions')
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-left" role="menu">

        <li>
            <a href="{{ route('sw.operators.edit', $row->id) }}" class="btn-modal"
                data-container=".sw_operator_modal">
                <i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')
            </a>
        </li>

        @if ((float) ($row->short_amount ?? 0) > 0)
            <li>
                <a href="{{ route('sw.operators.recover-shortage', $row->id) }}" class="btn-modal"
                    data-container=".sw_operator_modal">
                    <i class="fa fa-hand-o-down"></i> @lang('sw::lang.recover_shortage')
                    <span class="text-muted">({{ number_format((float) $row->short_amount, 2) }})</span>
                </a>
            </li>
        @endif

        @if ((float) ($row->excess_amount ?? 0) > 0)
            <li>
                <a href="{{ route('sw.operators.pay-excess', $row->id) }}" class="btn-modal"
                    data-container=".sw_operator_modal">
                    <i class="fa fa-hand-o-up"></i> @lang('sw::lang.pay_excess')
                    <span class="text-muted">({{ number_format((float) $row->excess_amount, 2) }})</span>
                </a>
            </li>
        @endif

        {{--
            Ledger.
            
            Opens PetroGeneral's operator ledger rather than a second
            implementation.
            
            That ledger already merges Finance journals marked "Show in Ledger =
            Pump Operator" - IS2068 - alongside the account transactions and the
            opening balance. Rebuilding it here would mean a second copy of that
            merge, and two ledgers that eventually disagree about what an
            operator owes is the worst possible outcome for a balance.
            
            It reads shared data - the operator's account transactions - rather
            than anything SW records, which is the same reasoning that has SW
            share pump_operators instead of copying them.
            
            Guarded: if PetroGeneral is absent the item is simply not offered,
            rather than the whole page failing on a missing route.
        --}}
        @if (Route::has('petrogeneral.pump_operators.ledger'))
            <li>
                <a href="{{ route('petrogeneral.pump_operators.ledger', [
                    'pump_operator_id' => $row->id,
                    'start_date' => date('Y-m-01'),
                    'end_date' => date('Y-m-t'),
                ]) }}" target="_blank">
                    <i class="fa fa-book"></i> @lang('sw::lang.ledger')
                </a>
            </li>
        @endif

        <li class="divider"></li>

        <li>
            <a href="{{ route('sw.operators.toggle', $row->id) }}"
                onclick="return confirm('{{ ($row->active ?? 1) ? __('sw::lang.confirm_deactivate') : __('sw::lang.confirm_activate') }}')">
                <i class="fa fa-power-off"></i>
                {{ ($row->active ?? 1) ? __('sw::lang.deactivate') : __('sw::lang.activate') }}
            </a>
        </li>

    </ul>
</div>
