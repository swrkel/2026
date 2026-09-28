                @if(auth()->user()->can('upload_images'))
                    <div class="row">
                        <div class="col-md-3">
                            <h4><label>@lang( 'lang_v1.upload_images' )</label></h4>
                        </div>
                        <div class="col-md-9">
                            @can('upload_images')
                                <div class="col-md-12">
                                    <div class="checkbox">
                                        <label>
                                            {!! Form::checkbox('permissions[]', 'upload_images', in_array('upload_images',
                                            $role_permissions),
                                            [ 'class' => 'input-icheck']); !!} {{ __('lang_v1.upload_images') }}
                                        </label>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    </div>
                    <hr class="blue-hr">
                @endif
