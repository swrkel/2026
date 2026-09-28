<tr>
  <td>
    {!! Form::select('issue_customer_bill['.$index.'][product_id]', $products, null, ['class' => 'form-control select2
    product_id', 'style' => 'width:100%;', 'required', 'placeholder' =>
    __( 'petro::lang.please_select')]) !!}
  </td>
  <td>
    {!! Form::text('issue_customer_bill['.$index.'][unit_price]', number_format(0, $currency_precision, '.', ','), ['class' => 'form-control unit_price text-right', 'style' =>
    'width: 120px;', 'placeholder' => __('petro::lang.unit_price'), 'readonly']) !!}
  </td>
  <td>
    {!! Form::text('issue_customer_bill['.$index.'][qty]', number_format(0, $currency_precision, '.', ','), ['class' => 'form-control qty text-right', 'style' => 'width:
    120px;', 'placeholder' => __('petro::lang.qty')]) !!}
  </td>
  <td>
    {!! Form::text('issue_customer_bill['.$index.'][discount]', 0, ['class' => 'form-control discount text-right', 'style' =>
    'width: 120px;', 'placeholder' => __('petro::lang.discount')]) !!}
  </td>
{{--  <td>--}}
{{--    {!! Form::text('issue_customer_bill['.$index.'][tax]', 0, ['class' => 'form-control tax text-right', 'style' => 'width:--}}
{{--    120px;', 'placeholder' => __('petro::lang.tax')]) !!}--}}
{{--  </td>--}}
  <td>
    {!! Form::text('issue_customer_bill['.$index.'][sub_total]', number_format(0, $currency_precision, '.', ','), ['class' => 'form-control sub_total text-right', 'style' =>
    'width: 120px;', 'placeholder' => __('petro::lang.sub_total')]) !!}
  </td>
  <td>
    <button type="button" class="btn btn-xs btn-primary minus_row" style="margin-top: 6px;">-</button>
</td>
</tr>