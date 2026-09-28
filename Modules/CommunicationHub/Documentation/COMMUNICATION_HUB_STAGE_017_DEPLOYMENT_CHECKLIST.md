# Stage 017 Deployment Checklist

1. Replace the CommunicationHub module folder with the contents of this parcel.
2. Run `Database/SQL/18_ENTERPRISE_EXCELLENCE_FINAL_STAGE.sql` in every tenant database.
3. Run/compare `Database/SQL/MASTER_COMMUNICATION_HUB_SQL_STAGE_017_ENTERPRISE_EXCELLENCE.sql` only where a full rebuild is required.
4. Clear Laravel cache if your server uses route/config cache.
5. Open Communication Hub > Enterprise Excellence > Final Enterprise Audit.
6. Confirm all Stage 017 tables show OK.
7. Test Event Registry, Provider Health, Cost Centre, Advanced Scheduler, and Public API Client creation.
