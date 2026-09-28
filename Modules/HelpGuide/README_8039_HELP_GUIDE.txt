8039 Help Guide - Changes
1. Super Admin article management page added for module-wise Help Guide articles.
2. User-wise Help Guide language dropdown added with Make Default action.
3. English remains system default when a user has no saved preference.
4. User default is stored in hg_user_language_preferences and auto-loaded on each Help Guide access.
5. Translations are persistent in hg_article_translations.
6. Saving/changing the English source article automatically refreshes all active non-English translations.
7. Translation providers: MyMemory (default), Google Cloud Translate, or LibreTranslate, configured only inside HelpGuide config/env.
8. Translation failure never destroys the English source. Failed translations remain marked pending and can be retried from Super Admin.
9. Consolidated SQL remains repeat-safe and uses CREATE TABLE IF NOT EXISTS for all Help Guide tables.
10. Existing 8038 business/module visibility behavior is preserved.
