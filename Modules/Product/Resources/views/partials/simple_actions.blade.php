<div class="btn-group">
<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown">@lang('product::common.actions') <span class="caret"></span></button>
<ul class="dropdown-menu dropdown-menu-left" role="menu">
<li><a href="{{ route('product.'.$route.'.edit', $row->id) }}"><i class="fa fa-edit"></i> @lang('product::common.edit')</a></li>
<li><a href="#" class="product-delete" data-href="{{ route('product.'.$route.'.destroy', $row->id) }}"><i class="fa fa-trash"></i> @lang('product::common.delete')</a></li>
</ul></div>
