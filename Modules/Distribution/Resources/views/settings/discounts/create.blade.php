<div class="modal-dialog modal-xl">
    <form action="{{ action('\Modules\Distribution\Http\Controllers\DiscountController@store') }}" method="POST">
        @csrf
        <div class="modal-content" style="position: relative;">

            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; position: relative;">
                <h4>Add Discount</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="position: absolute; top: 0; right: 0; margin: 0; padding: 10px 15px; z-index: 1051; border: none; background: transparent; cursor: pointer;">
                    <span aria-hidden="true" style="font-size: 28px; font-weight: bold; color: #000; line-height: 1;">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                {{-- Top Row: Date & Time, Product Category, Product Sub Category, Add Product Button --}}
                <div class="row" style="margin-bottom: 20px;">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Date & Time</label>
                            @if(!empty($auto_date_time))
                                <input type="text" name="date_time" class="form-control" 
                                    value="{{ date('Y-m-d H:i:s') }}" readonly required>
                            @else
                                <input type="datetime-local" name="date_time" class="form-control" 
                                    value="{{ now()->format('Y-m-d\TH:i') }}" readonly required>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Product Category</label>
                            <select name="category_id" id="category_select" class="form-control select2-search">
                                <option value="">All</option>
                                @foreach ($main_categories as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Product Sub Category</label>
                            <select name="sub_category_id" id="sub_category_select" class="form-control select2-search">
                                <option value="">All</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="button" class="btn btn-success btn-sm" id="add_product_row" style="width: 100%;">
                                <i class="fa fa-plus"></i> Add Product
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Product rows table --}}
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="product_table">
                        <thead>
                            <tr>
                                <th style="width:25%">Product</th>
                                <th style="width:15%">Unit</th>
                                <th style="width:10%">Qty</th>
                                <th style="width:15%">Discount Type</th>
                                <th style="width:15%">Max Discount</th>
                                <th style="width:5%">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>

        </div>
    </form>
</div>
