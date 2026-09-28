<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Modules\PetroPDNew\Http\Requests\DocumentStoreRequest;
use Modules\PetroPDNew\Services\PdnewBusinessFeatureService;
use Modules\PetroPDNew\Services\PdnewDocumentService;

class DocumentController extends PdnewController
{
    public function store(
        DocumentStoreRequest $request,
        int $settlement,
        PdnewDocumentService $service,
        PdnewBusinessFeatureService $features
    ) {
        $features->authorizeSettlementTab('documents', $this->context->businessId());

        try {
            $service->store(
                $this->settlement($settlement),
                $request->file('file'),
                $request->validated(),
                $this->context->userId()
            );

            return back()->with('success', 'Settlement document uploaded.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function download(
        int $document,
        PdnewDocumentService $service,
        PdnewBusinessFeatureService $features
    ) {
        $features->authorizeSettlementTab('documents', $this->context->businessId());

        try {
            $model = $this->document($document);
            $service->assertDownloadable($model);

            return Storage::disk((string) $model->disk)->download(
                (string) $model->path,
                (string) $model->original_name
            );
        } catch (\RuntimeException $exception) {
            abort(404, $exception->getMessage());
        }
    }

    public function destroy(
        int $document,
        PdnewDocumentService $service,
        PdnewBusinessFeatureService $features
    ) {
        $features->authorizeSettlementTab('documents', $this->context->businessId());

        try {
            $service->delete($this->document($document), $this->context->userId());

            return back()->with('success', 'Settlement document removed.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }
}
