<?php

return array (
  'asset_version' => '676cfb89723ba',

  // Default emptied: kit bb1c887317 returns 403 on every page load, twice.
  // Icons come from the self-hosted CSS in javascripts.blade.php, which is what
  // the comment there already says. Set FONT_AWESOME_ID in .env for a working kit.
  'font_awesome_id' => env('FONT_AWESOME_ID', ''),
);