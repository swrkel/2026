@extends('layouts.app')
@section('title', __('vat::lang.vat_invoice'))

@section('content')

<style>
    .vat-card {
        background: #fff;
        padding: 20px 20px;
        border-radius: 6px;
        border: 1px solid #e5e5e5;
        box-shadow: 0 2px 6px rgba(0,0,0,.05);
    }
    .vat-card-header {
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
        font-weight: 600;
        font-size: 15px;
    }
    .vat-card-body {
        padding: 20px;
    }
    .vat-logo-preview {
        max-height: 110px;
        padding: 8px;
        border: 1px dashed #bbb;
        border-radius: 4px;
        background: #fafafa;
    }
    .vat-current-logo {
        padding: 6px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: #fff;
        width: 100px;
    }
    .vat-hint {
        font-size: 12px;
        color: #777;
        margin-top: 4px;
    }
</style>

<section class="content">
    <div class="row">
        @include('vat::vat_invoice2.partials.nav')
    </div>

    <div class="row" style="margin-top: 20px;">
        <div class="col-md-6 col-md-offset-3">

            <div class="vat-card">
                <div class="vat-card-header">
                    <i class="fa fa-image"></i> VAT Invoice Logo
                </div>

                <form action="{{ action('\Modules\Vat\Http\Controllers\VatInvoice2Controller@logoUploadSave') }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf

                    <div class="vat-card-body">

                        {{-- Existing Logo --}}
                        @if(!empty($vat_logo))
                            <div class="text-center">
                                <label>Current Logo</label><br><br>
                                <img
                                    src="{{ asset($vat_logo) }}"
                                    class="vat-current-logo"
                                    @if(!empty($vat_logo_width)) style="width: {{ $vat_logo_width }}px;" @endif
                                    @if(!empty($vat_logo_height)) style="height: {{ $vat_logo_height }}px;" @endif
                                >
                            </div>
                            <hr>
                        @endif

                        {{-- Upload --}}
                        <div class="form-group">
                            <label>Upload New Logo</label>
                            <input
                                type="file"
                                name="vat_logo"
                                id="vat_logo"
                                class="form-control"
                                accept="image/*"
                            >
                            <div class="vat-hint">
                                JPG / PNG only
                            </div>
                        </div>

                        {{-- Logo Size --}}
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Logo Width (px)</label>
                                    <input
                                        type="number"
                                        name="vat_logo_width"
                                        class="form-control"
                                        placeholder="e.g. 120"
                                        value="{{ $vat_logo_width ?? '' }}"
                                    >
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Logo Height (px)</label>
                                    <input
                                        type="number"
                                        name="vat_logo_height"
                                        class="form-control"
                                        placeholder="e.g. 80"
                                        value="{{ $vat_logo_height ?? '' }}"
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="vat-hint">
                            Leave empty to keep original size
                        </div>

                        {{-- Preview --}}
                        <div class="form-group text-center" id="previewBox" style="display:none;">
                            <label>Preview</label><br><br>
                            <img id="logoPreview" class="vat-logo-preview">
                        </div>

                    </div>

                    <div class="box-footer text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i>
                            {{ empty($vat_logo) ? 'Save Logo' : 'Update Logo' }}
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</section>

@endsection

@section('javascript')
<script>
$(function () {

    function applySize() {
        const w = $('input[name="vat_logo_width"]').val();
        const h = $('input[name="vat_logo_height"]').val();

        $('#logoPreview').css({
            width: w ? w + 'px' : 'auto',
            height: h ? h + 'px' : 'auto'
        });
        $(".vat-current-logo").css({
            width: w ? w + 'px' : 'auto',
            height: h ? h + 'px' : 'auto'
        });
    }

    $('#vat_logo').on('change', function () {
        if (this.files && this.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#logoPreview').attr('src', e.target.result);
                applySize();
                $('#previewBox').fadeIn(150);
            };
            reader.readAsDataURL(this.files[0]);
        }
    });

    $('input[name="vat_logo_width"], input[name="vat_logo_height"]').on('input', applySize);

});
</script>
@endsection
