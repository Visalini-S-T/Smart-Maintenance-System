# Smart Maintenance & Downtime Analytics System

A portfolio-ready factory maintenance web application.

## Features

### Stage 1 - Static / browser logic
- Dashboard with realistic dummy data
- Machine health score calculated in JavaScript
- MTBF calculated in JavaScript
- Downtime and availability KPIs
- Client-side form validation
- Responsive dashboard and charts

### Stage 2 - Dynamic / database
- PHP + MySQL backend
- Secure authentication using `password_hash()` / `password_verify()`
- Role-based access: Admin, Supervisor, Technician
- Machine CRUD
- Breakdown logging
- Repeat-breakdown alert
- Preventive maintenance schedules
- Work-order workflow: Reported -> Assigned -> In Progress -> Closed
- Spare-parts stock deduction when a work order is closed
- Low-stock alerts
- CSV export
- PDF-ready report page / print-to-PDF
- Audit-friendly timestamps

### Optional extras included
- QR code generation for machine IDs
- Email notification service scaffold (SMTP-ready)
- Notification records

## Tech stack

- PHP 8+
- MySQL 8+
- HTML5 / CSS3 / JavaScript
- Chart.js
- QRCode.js
- jsPDF + AutoTable
- Bootstrap Icons (CDN)

## Quick setup

1. Install XAMPP/WAMP/Laragon with PHP and MySQL.
2. Copy this folder into your web root, e.g.:
   `htdocs/smart-maintenance-system`
3. Create a MySQL database.
4. Import `database/schema.sql`.
5. Edit `config/database.php`.
6. Open:
   `http://localhost/smart-maintenance-system/`
7. Click **Seed Demo Data** once if the database is empty.
8. Demo accounts:
   - Admin: `admin@example.com` / `Admin@123`
   - Supervisor: `supervisor@example.com` / `Supervisor@123`
   - Technician: `technician@example.com` / `Technician@123`

## Security notes

- Passwords are hashed with `password_hash()`.
- Database queries use PDO prepared statements.
- Sessions are used for authentication.
- Role checks are enforced server-side.
- For production, add CSRF tokens, HTTPS, rate limiting, secure cookie flags, stronger secret management, and SMTP credentials via environment variables.

## Metrics

### Availability
`Availability = (Planned Operating Time - Downtime) / Planned Operating Time * 100`

### MTBF
`MTBF = Operating Time / Number of Failures`

### Health score
This demo uses a transparent weighted score based on availability, maintenance compliance, and repeat-breakdown frequency. The formula is intentionally simple so it can be explained in an interview.

## Demo flow

1. Login as Admin.
2. Open Dashboard.
3. Show health scores, MTBF, downtime and alerts.
4. Add a breakdown.
5. Add another breakdown for the same machine and show the repeat-breakdown alert.
6. Create a work order.
7. Assign it to a technician.
8. Close it using a spare part.
9. Show inventory deduction.
10. Export CSV / print the PDF report.
11. Scan a machine QR code (or open the generated QR).
