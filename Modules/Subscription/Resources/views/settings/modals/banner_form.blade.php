<style>
.current-banner {
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
    max-height: 120px;
}

.banner-preview {
    padding: 8px;
    border: 1px dashed #bbb;
    border-radius: 4px;
    background: #fafafa;
    max-height: 120px;
}
</style>

<div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
        <form method="post" enctype="multipart/form-data"
            action="{{ isset($banner)
                ? action('\Modules\Subscription\Http\Controllers\SubscriptionBannerController@update', $banner->id)
                : action('\Modules\Subscription\Http\Controllers\SubscriptionBannerController@store') }}"
            id="add_banner_form" class="banner_form">
            @csrf
            @if (isset($banner))
                @method('PUT')
            @endif

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title">
                    @lang('subscription::lang.banner')
                    @if (isset($banner))
                        - @lang('subscription::lang.edit')
                    @else
                        - @lang('subscription::lang.upload')
                    @endif
                </h4>
            </div>

            <div class="modal-body">

                {{-- Existing Banner --}}
                @if (isset($banner) && !empty($banner->file_path))
                    <div class="text-center">
                        <label>@lang('subscription::lang.current_banner')</label><br><br>
                        <img src="{{ asset($banner->file_path) }}" class="current-banner"
                            style="
                    {{ $banner->width ? 'width:' . $banner->width . 'px;' : '' }}
                    {{ $banner->height ? 'height:' . $banner->height . 'px;' : '' }}
                ">
                    </div>
                    <hr>
                @endif

                {{-- Upload --}}
                <div class="form-group">
                    <label>@lang('subscription::lang.banner_file')</label>
                    <input type="file" class="form-control" id="file_path" name="file_path" accept="image/*"
                        {{ !isset($banner) ? 'required' : '' }}>
                </div>

                {{-- Size --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('subscription::lang.width') (px)</label>
                            <input type="number" class="form-control" name="banner_width"
                                value="{{ $banner->width ?? '' }}" placeholder="e.g. 300">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>@lang('subscription::lang.height') (px)</label>
                            <input type="number" class="form-control" name="banner_height"
                                value="{{ $banner->height ?? '' }}" placeholder="e.g. 150">
                        </div>
                    </div>
                </div>

                <p class="text-muted text-center">
                    @lang('subscription::lang.leave_blank_to_keep_original_size')
                </p>

                {{-- Preview --}}
                <div class="form-group text-center" id="previewBox" style="display:none;">
                    <label>@lang('subscription::lang.preview')</label><br><br>
                    <img id="bannerPreview" class="banner-preview">
                </div>

            </div>


            <div class="modal-footer">
                <button type="submit" class="btn btn-primary" id="save_banner_btn">
                    @if (isset($banner))
                        @lang('subscription::lang.update')
                    @else
                        @lang('subscription::lang.upload')
                    @endif
                </button>
                <button type="button" class="btn btn-default" data-dismiss="modal">
                    @lang('subscription::lang.close')
                </button>
            </div>
        </form>
    </div>
</div>
