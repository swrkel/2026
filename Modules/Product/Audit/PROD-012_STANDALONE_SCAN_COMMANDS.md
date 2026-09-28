# PROD-012 – Scan Commands

Run these from project root after uploading the package:

```bash
grep -R "view('product\|view("product\|@include('product\|@include("product" -n Modules/Product --include='*.php' --include='*.blade.php'
grep -R "@include('layouts\|@extends('layouts\|view('brand\|view('unit" -n Modules/Product --include='*.php' --include='*.blade.php'
grep -R "use App\\" -n Modules/Product --include='*.php'
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

Expected: old Product/Brand/Unit view references should now resolve inside the Product module.
