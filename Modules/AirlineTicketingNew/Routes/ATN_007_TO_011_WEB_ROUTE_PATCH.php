<?php
// Add these lines inside the existing authenticated AirlineTicketingNew route group:
require module_path('AirlineTicketingNew', 'Routes/settlements.php');
require module_path('AirlineTicketingNew', 'Routes/operations.php');
require module_path('AirlineTicketingNew', 'Routes/reports.php');
require module_path('AirlineTicketingNew', 'Routes/admin.php');
