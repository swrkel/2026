SELECT COUNT(*) ticket_count FROM atn_tickets;
SELECT COUNT(*) reservation_count FROM atn_reservations;
SELECT COUNT(*) invoice_count FROM atn_invoices;
SELECT COUNT(*) payment_count FROM atn_payments;
SELECT COUNT(*) module_permission_count FROM permissions WHERE name LIKE 'airline_ticketing_new.%';
