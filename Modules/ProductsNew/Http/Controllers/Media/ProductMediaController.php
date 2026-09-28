<?php
namespace Modules\ProductsNew\Http\Controllers\Media;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\ProductsNew\Services\Media\ProductMediaService;
use Modules\ProductsNew\Services\ProductLookupService;

class ProductMediaController extends Controller
{
    protected ProductMediaService $media;
    protected ProductLookupService $lookups;

    public function __construct(ProductMediaService $media, ProductLookupService $lookups)
    {
        $this->media = $media;
        $this->lookups = $lookups;
    }

    public function index(Request $request)
    {
        return view('productsnew::media.index', [
            'mediaItems' => $this->media->list($request),
            'products' => $this->lookups->products($request),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['product_id'=>'required|integer','title'=>'nullable|max:191']);
        $this->media->store($request);
        return back()->with('status', __('productsnew::product.media_uploaded'));
    }

    public function destroy(Request $request, $media)
    {
        $this->media->remove($request, (int)$media);
        return back()->with('status', __('productsnew::product.media_deleted'));
    }
}
