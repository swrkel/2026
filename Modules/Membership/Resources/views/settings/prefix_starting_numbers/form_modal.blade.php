<div class="modal fade" id="mem_prefix_modal" tabindex="-1" role="dialog" aria-labelledby="memPrefixModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close mem-prefix-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="memPrefixModalLabel">
                    <span class="mem-prefix-title-add">{{ __('messages.add') }}</span>
                    <span class="mem-prefix-title-edit hide">{{ __('messages.edit') }}</span>
                    {{ __('membership::lang.prefix_and_starting_numbers') }}
                </h4>
            </div>

            {!! Form::open(['method' => 'post', 'id' => 'mem_prefix_form']) !!}
                <div class="modal-body">
                    <input type="hidden" id="mem_prefix_editing_id" value="">

                    <div class="form-group">
                        {!! Form::label('region', __('membership::lang.region') . ' *') !!}
                        {!! Form::text('region', null, [
                            'class' => 'form-control',
                            'required' => true,
                            'placeholder' => __('membership::lang.enter_region'),
                            'id' => 'mem_prefix_region'
                        ]) !!}
                    </div>

                    <div class="form-group">
                        {!! Form::label('prefix', __('membership::lang.prefix')) !!}
                        {!! Form::text('prefix', null, [
                            'class' => 'form-control',
                            'placeholder' => __('membership::lang.enter_prefix'),
                            'id' => 'mem_prefix_prefix',
                            'maxlength' => 10
                        ]) !!}
                    </div>

                    <div class="form-group">
                        {!! Form::label('starting_number', __('membership::lang.starting_number') . ' *') !!}
                        {!! Form::number('starting_number', null, [
                            'class' => 'form-control',
                            'required' => true,
                            'min' => 1,
                            'id' => 'mem_prefix_starting_number'
                        ]) !!}
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default mem-prefix-close" data-dismiss="modal" data-bs-dismiss="modal">
                        {{ __('messages.close') }}
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> {{ __('messages.save') }}
                    </button>
                </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
