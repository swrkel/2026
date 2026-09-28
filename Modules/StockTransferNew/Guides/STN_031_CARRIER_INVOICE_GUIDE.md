# STN_031 - Carrier / Freight Invoice Control

This package adds standalone carrier invoice controls for StockTransferNew.

## Features
- Carrier invoice register
- Freight/loading/unloading/other charge capture
- Draft, approve, cancel status flow
- Business-scoped duplicate invoice prevention
- Separate tenant SQL and permission inserts

## Notes
- This does not post to Finance directly.
- Finance posting can be bridged later through a clean service connector if required.
- Product master remains in the standalone Products module and is not duplicated here.
