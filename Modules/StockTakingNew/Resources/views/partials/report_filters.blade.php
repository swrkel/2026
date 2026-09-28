<div class="stk-filter-grid">
 <div><label>Date From</label><input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control"></div>
 <div><label>Date To</label><input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control"></div>
 <div><label>Location</label><select name="location_id" class="form-control stk-location-select"><option value="">All Locations</option>@foreach($locations as $id=>$name)<option value="{{ $id }}" @selected((string)request('location_id')===(string)$id)>{{ $name }}</option>@endforeach</select></div>
 <div><label>Store</label><select name="store_id" class="form-control stk-store-select" data-selected="{{ request('store_id') }}"><option value="">All Stores</option>@foreach($stores as $id=>$name)<option value="{{ $id }}" @selected((string)request('store_id')===(string)$id)>{{ $name }}</option>@endforeach</select></div>
 <div><label>Session</label><select name="session_id" class="form-control"><option value="">All Sessions</option>@foreach($sessions as $id=>$name)<option value="{{ $id }}" @selected((string)request('session_id')===(string)$id)>{{ $name }}</option>@endforeach</select></div>
 <div class="stk-filter-actions"><button class="stk-btn stk-btn-primary"><i class="fa fa-search"></i> Apply</button><a href="{{ url()->current() }}" class="stk-btn stk-btn-light">Reset</a></div>
</div>
