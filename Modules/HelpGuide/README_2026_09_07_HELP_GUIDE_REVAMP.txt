HELP GUIDE REVAMP - 07 SEP 2026
================================

1. Article saves are immediate.
   No translation provider is called from /superadmin/help-guide-modules/articles/save.

2. Background translations are stored in hg_translation_queue.
   One article + one language = one small queue item.

3. Laravel Scheduler processes a small batch every minute using:
   php artisan helpguide:translate-pending --limit=1

4. The normal Laravel scheduler cron must be active on the server:
   * * * * * cd /home/nivasa/public_html && php artisan schedule:run >> /dev/null 2>&1

5. Translation retries are automatic with backoff. Failed jobs remain visible in
   List Articles and can be queued again with Queue Translation.

6. Public Help Guide improvements:
   - top live search with type-ahead results
   - module and article auto-filtering
   - published article directory on the Help Guide home page
   - dedicated pleasant article reading page
   - language-aware search/results
   - floating up/down scroll controls and page progress rail

7. Admin improvements:
   - View button in List Articles
   - translation status badges
   - faster, cleaner article editor
   - dedicated article preview page

DATABASE
--------
Run the module migration OR the safe SQL file:
Database/SQL/2026_09_07_ADD_BACKGROUND_TRANSLATION_QUEUE.sql

No existing Help Guide table is dropped or recreated.
