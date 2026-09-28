{{--
    Shown when the module's tables are not present.

    Every page renders this instead of erroring, so a tenant where the SQL has
    not been run yet gets an instruction rather than a stack trace.
--}}
<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle"></i>
    Church Management is not installed in this database yet. Run
    <strong>Modules/ChurchManagement/Database/SQL/CHURCH_MANAGEMENT_MASTER_INSTALL.sql</strong>
    in this tenant database, or <code>php artisan module:migrate ChurchManagement</code>,
    then reload this page.
</div>
