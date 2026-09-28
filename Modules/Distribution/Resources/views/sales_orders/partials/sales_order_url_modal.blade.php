<div class="modal-dialog" role="document">
	<div class="modal-content">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<h4 class="modal-title">View Sales Order URL - Sales Order No: {{$sales_order->sales_order_no}}</h4>
		</div>
		<div class="modal-body">
			<div class="form-group">
				<input type="text" class="form-control" value="{{$url}}" id="sales_order_url" readonly>
				<p class="help-block">Copy this URL to share with customers</p>
			</div>
		</div>
		<div class="modal-footer">
		    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
		</div>
	</div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->

<script type="text/javascript">
	$('input#sales_order_url').click(function(){
		$(this).select().focus();
	});
</script>
