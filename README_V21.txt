TokoFlow V21 Functional Modern UI
================================

This build is based on the original TokoFlow PHP/MySQL application. The dashboard
was redesigned to closely follow the supplied reference image while keeping the
real application routes, authentication, database transactions, POS, products,
stock, reports, premium modules, backup, and settings.

Run:
1. Extract the TokoFlow folder to C:\xampp\htdocs\TokoFlow
2. Start Apache and MySQL in XAMPP
3. Open http://localhost/TokoFlow/
4. Use the existing TokoFlow database flow. If this is a fresh install, config.php
   initializes the schema and creates the demo account.

Demo:
email: admin@tokoflow.local
password: admin123

The dashboard values and Recent Orders are read from MySQL, so they change when
you actually add products and make transactions in Kasir.
