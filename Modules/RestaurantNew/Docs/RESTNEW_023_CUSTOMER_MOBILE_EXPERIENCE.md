# RESTNEW 023 - Customer Mobile Experience

Includes QR/table service request foundation, waiter call, bill request, customer assistance requests, public order tracking, event logs, assets, migration, and SQL.

Install order:
1. Apply migration or run SQL create file on tenant databases.
2. Run permission insert SQL.
3. Include `Routes/customer_experience.php` from module route provider if not auto-loaded.
4. Publish/copy assets to `public/modules/restaurantnew` if your deployment does not auto-publish module assets.
