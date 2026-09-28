# Communication Hub - Day to Day Operation Steps

## 1. First-time setup
1. Go to **Communication Hub → Providers**.
2. Click **Add Provider**.
3. Select channel: SMS, Email, WhatsApp, or Push.
4. Enter provider name, gateway/driver code, credentials, cost per message, daily limit, and active status.
5. Save the provider.
6. Use **Health** to confirm the provider is reachable.

## 2. Create SMS packages for sale
1. Go to **Communication Hub → SMS Packages**.
2. Enter package name, number of credits, cost price, selling price, validity days, and description.
3. Save.
4. Verify package appears in the package list.

## 3. Register communication clients
1. Go to **Communication Hub → SMS Clients**.
2. Enter client name, business name, mobile number, email, and client type.
3. Save.
4. A zero-balance wallet is created automatically when wallet tables are available.

## 4. Refill client wallet
1. Go to **Communication Hub → Credit Refills**.
2. Select the client.
3. Select a package if applicable.
4. Enter credits, amount, reference number, and note.
5. Save.
6. Verify wallet balance under **Business Wallets** and transaction under **Credit Transactions**.

## 5. Send a single SMS
1. Go to **Communication Hub → Send SMS**.
2. Select client if the SMS is chargeable to a communication client.
3. Enter recipient number.
4. Type the SMS message.
5. Click **Queue SMS**.
6. System creates a pending message and deducts wallet credits if a client is selected.
7. Review under **Delivery Reports**.

## 6. Send bulk SMS
1. Go to **Communication Hub → Bulk SMS**.
2. Select client if applicable.
3. Paste numbers separated by comma, semicolon, or new line.
4. Type message.
5. Click **Queue Bulk SMS**.
6. System queues one message per unique recipient.
7. Review queued/sent/failed status in **Delivery Reports**.

## 7. Monitor delivery
1. Go to **Communication Hub → Delivery Reports**.
2. Check message status: Pending, Sent, Delivered, Failed, or Cancelled.
3. Review recipient, channel, cost, and date.
4. Failed records should be investigated from provider logs and retried once provider integration is enabled.

## 8. Review commercial profit
1. Go to **Communication Hub → SMS Profit Reports**.
2. Review total revenue, profit, client transactions, and package sales.
3. Use this report for daily sales and month-end reconciliation.

## 9. API client setup
1. Go to **Communication Hub → API Tokens**.
2. Select client if applicable.
3. Enter API client name and daily limit.
4. Save.
5. Provide generated API access details to the external client securely.
6. Monitor usage under **API Logs**.

## 10. Daily checks
1. Open **Communication Hub Dashboard**.
2. Check messages, sent, pending, failed, packages, clients, wallets, and profit cards.
3. Open **Provider Status** and confirm providers are healthy.
4. Open **Credit Transactions** and verify any new refills.
5. Open **Delivery Reports** and review failed messages.

## 11. Troubleshooting
- If dashboard shows missing table warning, run `Database/SQL/00_RUN_THIS_IN_EACH_TENANT_DATABASE.sql` in the tenant database.
- If sidebar does not show the module, ensure `modules_statuses.json` contains `"CommunicationHub": true`.
- If routes are missing, run `php artisan optimize:clear`.
- If counts show zero, verify that data exists in the current tenant database and that the logged-in user's business has access.

## 12. Deployment reminder
1. Replace only `Modules/CommunicationHub/` unless the package explicitly says otherwise.
2. Run the tenant SQL in the tenant database.
3. Clear cache using `php artisan optimize:clear`.
4. Login and test dashboard, packages, clients, wallets, send SMS, bulk SMS, delivery reports, and profit reports.


---

# RC1 Dashboard Standard - User Flow

## Daily start

1. Open **Communication Hub > Dashboard**.
2. Review the top 8 KPI cards.
3. Click **Failed Today** to open delivery/failed message review.
4. Click **Pending Queue** to open queue management.
5. Click **SMS Balance** to open wallet/balance page.
6. Use **Quick Operations** for Send SMS, Bulk SMS, Credit Refill, or Reports.

## Standard dashboard meaning

The 4-column dashboard is now the official ERP dashboard standard. Future module dashboards should show the same structure so users can work consistently across modules.
