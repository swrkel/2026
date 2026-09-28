<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyServicePackage;

class PackageService
{
    public function indexData(): array { return ['packages' => BeautyServicePackage::latest()->paginate(25)]; }
    public function editData($id): array { return ['package' => BeautyServicePackage::findOrFail($id)]; }
    public function store(array $data): BeautyServicePackage { return BeautyServicePackage::create($data); }
    public function update($id, array $data): BeautyServicePackage { $package = BeautyServicePackage::findOrFail($id); $package->update($data); return $package; }
    public function reportData(): array { return ['packages' => BeautyServicePackage::latest()->get()]; }
}
