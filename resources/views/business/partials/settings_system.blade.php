<div class="pos-tab-content">
     <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('theme_color', __('lang_v1.theme_color')); !!}
                {!! Form::select('theme_color', $theme_colors,   $business->theme_color, 
                    ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'style' => 'width: 100%;']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $page_entries = [25 => 25, 50 => 50, 100 => 100, 200 => 200, 500 => 500, 1000 => 1000, -1 => __('lang_v1.all')];
                @endphp
                {!! Form::label('default_datatable_page_entries', __('lang_v1.default_datatable_page_entries')); !!}
                {!! Form::select('common_settings[default_datatable_page_entries]', $page_entries, !empty($common_settings['default_datatable_page_entries']) ? $common_settings['default_datatable_page_entries'] : 25 , 
                    ['class' => 'form-control select2', 'style' => 'width: 100%;', 'id' => 'default_datatable_page_entries']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <div class="checkbox">
                  <label>
                    {!! Form::checkbox('enable_tooltip', 1, $business->enable_tooltip , 
                    [ 'class' => 'input-icheck']); !!} {{ __( 'business.show_help_text' ) }}
                  </label>
                </div>
            </div>
        </div>
		<div class="col-sm-4">
            <div class="form-group">
                {!! Form::label('captch_site_key','reCAPTCHA Site Key'); !!}
                {!! Form::text('common_settings[captch_site_key]',!empty($common_settings['captch_site_key']) ? $common_settings['captch_site_key'] : null , 
                    ['class' => 'form-control', 'style' => 'width: 100%;']); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $erp_font_families = [
                        'Calibri' => 'Calibri',
                        'Arial' => 'Arial',
                        'Segoe UI' => 'Segoe UI',
                        'Tahoma' => 'Tahoma',
                        'Verdana' => 'Verdana',
                        'Roboto' => 'Roboto',
                        'Inter' => 'Inter',
                        'Times New Roman' => 'Times New Roman',
                        'Georgia' => 'Georgia',
                        'Courier New' => 'Courier New',
                    ];
                @endphp
                {!! Form::label('font_style', 'Font Style'); !!}
                {!! Form::select('font_style', $erp_font_families, !empty($business->font_style) ? $business->font_style : 'Calibri',
                    ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                @php
                    $erp_font_sizes = [
                        '12' => '12 px',
                        '13' => '13 px',
                        '14' => '14 px',
                        '15' => '15 px',
                        '16' => '16 px',
                        '18' => '18 px',
                        '20' => '20 px',
                    ];
                @endphp
                {!! Form::label('font_size', 'Font Size'); !!}
                {!! Form::select('font_size', $erp_font_sizes, !empty($business->font_size) ? $business->font_size : '14',
                    ['class' => 'form-control select2', 'style' => 'width: 100%;', 'placeholder' => __('messages.please_select')]); !!}
            </div>
        </div>

    </div>
</div>