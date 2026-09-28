{{--
    Assign operators to a shift.

    Pending on the left, assigned on the right, arrows between - the working the
    page your operators already know uses.

    On save, only the right-hand list is submitted; the left is a working area.
--}}

<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">

        {!! Form::open([
            'url' => route('sw.shifts.assignment.save', $shift->id),
            'method' => 'post',
            'id' => 'sw_assign_form',
        ]) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">
                @lang('sw::lang.assign_operators') &mdash; {{ $shift->sw_shift_no }}
            </h4>
        </div>

        <div class="modal-body">

            @if (! $shift->isOpen())
                <div class="alert alert-warning" style="font-size:13px">
                    <i class="fa fa-exclamation-triangle"></i>
                    @lang('sw::lang.changing_a_closed_shift')
                </div>
            @endif

            <div class="row">

                <div class="col-md-5">
                    <label style="font-size:14px">@lang('sw::lang.pending_assignment')</label>
                    <select multiple class="form-control sw-assign-list" id="sw_assign_left" size="10">
                        @foreach ($pending as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 text-center sw-assign-arrows">
                    <button type="button" class="btn btn-success btn-block" id="sw_assign_right">
                        <i class="fa fa-arrow-right"></i>
                    </button>
                    <button type="button" class="btn btn-warning btn-block" id="sw_assign_left_btn">
                        <i class="fa fa-arrow-left"></i>
                    </button>
                </div>

                <div class="col-md-5">
                    <label style="font-size:14px">@lang('sw::lang.assigned_to_this_shift')</label>
                    <select multiple class="form-control sw-assign-list" id="sw_assign_right_list" size="10">
                        @foreach ($assigned as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

            </div>

            @if (! $shift->isOpen())
                <div class="form-group" style="margin-top:15px">
                    {!! Form::label('reason', __('sw::lang.reason_for_change') . ':*') !!}
                    {!! Form::textarea('reason', null, [
                        'class' => 'form-control', 'rows' => 2, 'required',
                        'placeholder' => __('sw::lang.reason_placeholder'),
                    ]) !!}
                    <span class="help-block" style="margin-bottom:0">
                        @lang('sw::lang.reason_appears_in_logs')
                    </span>
                </div>
            @endif

        </div>

        <div class="modal-footer">
            <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}

    </div>
</div>


<style>
/*
 | The two operator lists.
 |
 | A default multi-select renders names at 13px in a cramped box. These are
 | read and clicked at speed, so both the text and the row height are lifted.
*/
#sw_assign_left,
#sw_assign_right_list {
    font-size: 15px;
    min-height: 260px;
    padding: 4px;
    border-radius: 8px;
}

#sw_assign_left option,
#sw_assign_right_list option {
    padding: 8px 10px;
    border-radius: 6px;
    margin-bottom: 2px;
}

#sw_assign_left option:checked,
#sw_assign_right_list option:checked {
    background: #eef5ff linear-gradient(0deg, #eef5ff 0%, #eef5ff 100%);
    font-weight: 600;
}

.sw-assign-arrows .btn {
    font-size: 15px;
    padding: 8px 0;
}
</style>

<script>
$(function () {

    function swMove($from, $to) {
        $from.find('option:selected').each(function () {
            $to.append($(this));
        });
        // Alphabetical, so a long list stays findable.
        var opts = $to.find('option').sort(function (a, b) {
            return $(a).text().localeCompare($(b).text());
        });
        $to.empty().append(opts);
    }

    $('#sw_assign_right').on('click', function () {
        swMove($('#sw_assign_left'), $('#sw_assign_right_list'));
    });

    $('#sw_assign_left_btn').on('click', function () {
        swMove($('#sw_assign_right_list'), $('#sw_assign_left'));
    });

    // Double-click moves too - quicker than select-then-arrow.
    $('#sw_assign_left').on('dblclick', function () {
        swMove($(this), $('#sw_assign_right_list'));
    });
    $('#sw_assign_right_list').on('dblclick', function () {
        swMove($(this), $('#sw_assign_left'));
    });

    /*
     | Everything in the right-hand list is submitted, selected or not.
     |
     | An unselected option is not posted by a browser, so without this a user
     | who assigned four operators and clicked Save would assign none of them.
    */
    $('#sw_assign_form').on('submit', function () {
        var $form = $(this);
        $form.find('input[name="operator_ids[]"]').remove();

        $('#sw_assign_right_list option').each(function () {
            $form.append($('<input>')
                .attr('type', 'hidden')
                .attr('name', 'operator_ids[]')
                .val($(this).val()));
        });
    });

});
</script>
