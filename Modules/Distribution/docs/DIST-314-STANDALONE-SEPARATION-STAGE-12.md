# DIST-314 - Distribution Standalone Separation Stage 12

Focus: remaining route/action dependency cleanup.

Changes included:
- Added Distribution-owned invoice payment display controller/service/view.
- Re-pointed invoice View Payment action to Distribution invoice payment route.
- Removed direct dependency on SellPosController for invoice URL from Distribution invoice list.
- Added Distribution-owned vehicle number validation route and controller method.
- Re-pointed fleet create/edit vehicle validation to Distribution route.
- Added Distribution-prefixed Free Issue route aliases and updated module references.

This stage intentionally avoids changing core payment posting logic to protect currently working payment-save behaviour.
