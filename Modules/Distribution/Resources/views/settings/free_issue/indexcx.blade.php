<!-- Main content -->
<section class="content">
  <div class="col-md-3">
    <div class="form-group">
        {!! Form::label('category_id', __('product.category') . ':') !!}
        {!! Form::select('category_id', 'null', null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]) !!}
    </div>
</div>
 
</section>
<!-- /.content -->
 