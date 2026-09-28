<?php
namespace Modules\EggManagement\Http\Controllers;

class SettingsController extends BaseController
{
    public function index()
    {
        $items = [
            ['label'=>'Egg Grades','route'=>'egg.grades.index','permission'=>'egg.settings.view','icon'=>'fa fa-tags','description'=>'Manage egg grades and weight ranges.'],
            ['label'=>'Products New Mapping','route'=>'egg.product-mappings.index','permission'=>'egg.product-mappings.view','icon'=>'fa fa-link','description'=>'Connect Egg grades with Products New items.'],
            ['label'=>'Layer Flocks','route'=>'egg.flocks.index','permission'=>'egg.flocks.view','icon'=>'fa fa-list-alt','description'=>'Maintain the layer flocks used for daily collections.'],
            ['label'=>'Integration Outbox','route'=>'egg.integrations.index','permission'=>'egg.integrations.view','icon'=>'fa fa-exchange','description'=>'Review Egg integration events sent to common ERP modules.'],
        ];

        $user = auth()->user();
        if ($user) {
            $items = array_values(array_filter($items, function ($item) use ($user) {
                try {
                    return $user->can($item['permission']) || $user->can('egg.*');
                } catch (\Throwable $e) {
                    return true;
                }
            }));
        }

        return view('egg::settings.index', compact('items'));
    }
}
