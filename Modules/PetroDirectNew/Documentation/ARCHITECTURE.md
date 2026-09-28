# Petro Direct-New Architecture

- Module namespace: `Modules\\PetroDirectNew`
- Route prefix: `petro-direct-new`
- Database prefix: `pdirectnew_`
- Primary sidebar pages: Dashboard, Direct Settlement, List Direct Settlements, Pumper Management, Reports
- Internal pumper tabs: Pump Operators, Pumper Excess / Shortage Payments, Pumper Day Entries, Shift Summary, Payment Summary, Meters with Payments, Daily Pump Status, Close Shift, Current Meter, Unload Stock
- Shared application data is accessed only through `SharedMasterDataService` and `BusinessContext`.
- Legacy Petro Direct code is not called at runtime.
- All transactional writes are business- and location-scoped.
