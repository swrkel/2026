#!/usr/bin/env bash
set -euo pipefail
STAMP=$(date +%Y%m%d_%H%M%S)
mkdir -p DisabledModules
if [ -d Modules/Ran ]; then mv Modules/Ran "DisabledModules/RanLegacy_${STAMP}"; fi
echo "Legacy Ran archived. Copy the new Modules/Ran folder now, then run: php artisan optimize:clear"
