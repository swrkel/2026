-- Rice Mill Dashboard 8060 - shared passcode correction
-- 19 Sep 2026
--
-- Rice Mill Dashboard now authenticates with the existing shared user passcode
-- stored in users.pump_operator_passcode. The earlier Rice-specific dashboard
-- passcode table is obsolete and can be removed safely.

DROP TABLE IF EXISTS `rcm_dashboard_user_access`;
