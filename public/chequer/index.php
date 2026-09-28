<?php
// S380 Chequer physical-folder redirect.
// Some servers have /public/chequer as an asset folder, so /chequer can bypass Laravel and show blank.
// Redirect only the folder root to the new module URL; existing asset files in this folder remain unaffected.
header('Location: /chequer-module', true, 302);
exit;
