/*
 PETROPD PAYMENT INTEGRITY - SAFE ROLLBACK GUIDE
 Date: 23 July 2026

 IMPORTANT:
 - Do not drop the new columns or audit tables after financial records use them.
 - Do not delete repair or reconciliation evidence.
 - Application-code rollback is performed by restoring the previous tested files.
 - Database triggers may be temporarily disabled only during an emergency rollback,
   after stopping PetroPD writes and taking a full tenant database backup.

 The statements below are intentionally commented. Uncomment only after approval.
*/

/*
DROP TRIGGER IF EXISTS trg_pop_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_pop_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_scp_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_scp_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_scardp_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_scardp_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_schqp_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_schqp_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_scrp_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_scrp_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_sshp_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_sshp_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_sexp_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_sexp_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_dcol_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_dcol_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_dcard_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_dcard_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_dchq_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_dchq_authority_bu_20260723;
DROP TRIGGER IF EXISTS trg_dvch_authority_bi_20260723;
DROP TRIGGER IF EXISTS trg_dvch_authority_bu_20260723;
*/

SELECT 'No destructive rollback was executed. Restore application files first and retain all payment-integrity columns and audit evidence.' AS rollback_status;
