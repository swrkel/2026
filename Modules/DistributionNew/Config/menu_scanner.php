<?php
return [
    ['name'=>'Scanner Dashboard','route'=>'distribution-new.scanner.index','permission'=>'disnew.scanner.view'],
    ['name'=>'Barcode Loading','route'=>'distribution-new.scanner.loading','permission'=>'disnew.scanner.loading'],
    ['name'=>'Barcode Unloading','route'=>'distribution-new.scanner.unloading','permission'=>'disnew.scanner.unloading'],
    ['name'=>'Bin Locations','route'=>'distribution-new.scanner.bins','permission'=>'disnew.bins.view'],
    ['name'=>'Stock Verification','route'=>'distribution-new.scanner.verify','permission'=>'disnew.stock.verify'],
    ['name'=>'Barcode / QR Labels','route'=>'distribution-new.scanner.labels','permission'=>'disnew.labels.manage'],
];
