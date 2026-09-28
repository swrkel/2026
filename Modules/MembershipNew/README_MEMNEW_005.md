# MembershipNew_MEMNEW_005

Added:
- Central member/customer registry
- Business-member mapping table
- Business-specific customer history/ledger table
- Central member UI
- Business member linking UI
- Business customer history UI
- Central customer bridge integration service
- SQL for central registry architecture

SQL:
- SQL/MembershipNew/MembershipNew_MEMNEW_005.sql

Note:
If you later decide to use a separate central database, place `mn_central_members` there and keep mapping/history tables in each tenant database.
