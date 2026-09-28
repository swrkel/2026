<?php
return [
 'mobile_sync' => [
  'title' => 'Mobile & Offline Sync',
  'route' => 'disnew.mobile.sync_batches',
  'permission' => 'distribution_new.mobile_sync.view',
  'children' => [
   ['title'=>'Sync Batches','route'=>'disnew.mobile.sync_batches','permission'=>'distribution_new.mobile_sync.view'],
   ['title'=>'Mobile Devices','route'=>'disnew.mobile.devices','permission'=>'distribution_new.mobile_devices.view'],
  ],
 ],
];
