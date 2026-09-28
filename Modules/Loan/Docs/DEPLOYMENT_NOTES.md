# Loan Module Deployment Notes

- Upload/replace only `Modules/Loan/`.
- Ensure `modules_statuses.json` contains `"Loan": true`.
- Route prefix: `/loan`.
- Dashboard URL: `/loan/dashboard`.
- The route file intentionally does not set a controller namespace because the module RouteServiceProvider already applies `Modules\Loan\Http\Controllers`.
