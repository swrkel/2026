@extends('productsnew::layouts.app')
@section('productsnew_content')
<div class="productsnew-card">
    <div class="productsnew-card-header"><h3>{{ __('productsnew::product.media_center') }}</h3></div>
    <form method="post" action="{{ route('products-new.media.store') }}" class="productsnew-grid productsnew-grid-4">@csrf
        <div><label>Product</label><select name="product_id" class="form-control" required><option value="">Select</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} @if($p->sku)({{ $p->sku }})@endif</option>@endforeach</select></div>
        <div><label>Type</label><select class="form-control" name="media_type"><option value="image">Image</option><option value="manual">Manual</option><option value="certificate">Certificate</option><option value="video">Video</option><option value="other">Other</option></select></div>
        <div><label>Title</label><input class="form-control" name="title"></div>
        <div><label>File Path / Existing Upload Path</label><input class="form-control" name="file_path"></div>
        <div><label>External URL</label><input class="form-control" name="external_url"></div>
        <div><label>File Name</label><input class="form-control" name="file_name"></div>
        <label><input type="checkbox" name="is_primary" value="1"> Primary image</label>
        <div><label>Note</label><input class="form-control" name="note"></div>
        <div class="productsnew-actions"><button class="btn btn-success">Save Media</button></div>
    </form>
</div>
<div class="productsnew-card productsnew-media-grid">
@forelse($mediaItems as $m)
    <div class="productsnew-media-tile"><strong>{{ $m->title ?: strtoupper($m->media_type) }}</strong><span>{{ $m->file_name ?: $m->external_url ?: $m->file_path }}</span><small>{{ $m->is_primary ? 'Primary' : '' }}</small><form method="post" action="{{ route('products-new.media.destroy',$m->id) }}">@csrf @method('DELETE')<button class="btn btn-xs btn-danger">Remove</button></form></div>
@empty
    <p class="text-muted">No product media found.</p>
@endforelse
</div>
@endsection
