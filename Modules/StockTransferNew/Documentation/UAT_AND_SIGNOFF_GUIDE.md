# Stock Transfer-New UAT and Sign-off Guide

Use this guide after STN_014 final readiness and before enabling live stock movement.

1. Run all master and tenant SQL files in sequence.
2. Clear Laravel route/config/view cache.
3. Open `/stock-transfer-new/uat` and print the checklist.
4. Test setup, workflow, warehouse scanning, reports and security sections.
5. Record sign-offs at `/stock-transfer-new/uat/signoff`.
6. Check `/stock-transfer-new/uat/snapshot` before and after test transactions.

Do not clean test records until finance/stock users confirm the UAT result.
