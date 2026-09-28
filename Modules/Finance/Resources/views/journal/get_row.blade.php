{{--
    A journal line added by the + button.

    IS2178: this template had NO Account Sub Type column.

    Every added row was therefore missing the field the first two rows have, and
    its columns were one short - so what looked like the Account dropdown was
    sitting under the Sub Type heading, and every value from there rightwards was
    under the wrong title.

    It now carries the same six cells, in the same grid, as the rows in
    create.blade.php. The cascade script already binds by class, so the new
    dropdown works here with no further wiring.
--}}
<div class="fj-grid fj-row journal_row">
    <div>
        {!! Form::select(
            'journal[account_type_id][]',
            $account_types,
            null,
            [
                'class' => 'form-control select2 account_type_ids',
                'id' => 'account_type'.$index,
                'style' => 'width:100%',
                'required',
                'placeholder' => __('messages.please_select')
            ]
        ) !!}
    </div>

    <div>
        {!! Form::select(
            'journal[account_sub_type_id][]',
            [],
            null,
            [
                'class' => 'form-control select2 account_sub_type_ids',
                'id' => 'account_sub_type'.$index,
                'style' => 'width:100%',
                'placeholder' => 'Account Sub Type'
            ]
        ) !!}
    </div>

    <div>
        {!! Form::select(
            'journal[account_id][]',
            [],
            null,
            [
                'class' => 'form-control select2 account_ids',
                'id' => 'account_id'.$index,
                'style' => 'width:100%',
                'required',
                'placeholder' => __('messages.please_select')
            ]
        ) !!}
    </div>

    <div>
        {!! Form::text(
            'journal[debit_amount][]',
            null,
            [
                'class' => 'debit-top form-control debit_amount'.$index,
                'id' => 'debit'.$index,
                'placeholder' => __('account.amount')
            ]
        ) !!}
    </div>

    <div>
        {!! Form::text(
            'journal[credit_amount][]',
            null,
            [
                'class' => 'credit-top form-control credit_amount'.$index,
                'id' => 'credit'.$index,
                'placeholder' => __('account.amount')
            ]
        ) !!}
    </div>

    <div class="fj-action">
        <button type="button" class="btn btn-xs btn-danger remove_journal_input_row" data-index="{{$index}}"
                title="Remove this line">
            <i class="fa fa-minus"></i>
        </button>
    </div>
</div>
