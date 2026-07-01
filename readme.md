# City School

City School is a Symfony-based school management and multi-portal web application for students, teachers, parents, admins, and super admins.

## Project Info

- Status: under development
- Sector: Education
- Stack: PHP, Symfony, Twig, Doctrine, Stripe
- Local URL: `http://localhost:8000`

## Local Run

Start the application from the project root:

```powershell
php -d max_execution_time=300 -S localhost:8000 -t public public/router.php
```

## Demo Accounts

- Student: `student` / `student123`
- Teacher: `teacher` / `student123`
- Admin: `admin` / `student123`
- Parent: `parent` / `student123`
- Super Admin: `superadmin` / `student123`

## Main Modules

- Public marketing site: home, courses, values, events, admissions, pricing, contact
- Student portal
- Teacher portal
- Parent portal
- Admin portal
- Super admin portal
- Subscription and billing workflow
- Registration and approval workflow

## Current Features

- Student and parent self-registration request pages
- Admin approval/rejection of pending registration requests
- Approved registration tracking in admin user management
- Student impersonation from admin user management
- Stripe card and PayPal options in admin billing UI
- Billing renewal settings and subscription plan switching
- Test email action from admin billing page
- Multi-portal login pages with homepage return links
- Static asset routing fixed for CSS, fonts, JavaScript, and images

## Registration Flow

### Student

- Register: `/portal/register`
- Login: `/portal/login`
- New requests stay pending until approved by admin

### Parent

- Register: `/parent/register`
- Login: `/parent/login`
- New requests stay pending until approved by admin

### Admin Approval

- Open: `/admin/users`
- Review `Pending Self-Registration Requests`
- Approve or reject requests
- Approved accounts appear in `Approved Self-Registrations`

## Billing

Admin billing page:

```text
/admin/billing-subscription
```

Supported provider options:

- Stripe Card
- PayPal

Billing tools available in the UI:

- Update payment method
- Switch between Stripe and PayPal
- Renew subscription manually
- Toggle auto-renewal
- Simulate failed payment
- Send test email for mailer verification

## Gmail / Mailer Setup

Mailer uses `MAILER_DSN`.

Example Gmail configuration in `.env.local`:

```dotenv
MAILER_DSN=smtp://YOUR_GMAIL%40gmail.com:YOUR_APP_PASSWORD@smtp.gmail.com:587?encryption=tls&auth_mode=login
```

Notes:

- Use a Gmail App Password, not your normal Gmail password
- Enable 2-Step Verification on the Gmail account first
- After updating mail settings, clear cache:

```powershell
php bin/console cache:clear
```

## Data and Persistence Notes

- Self-registration requests are persisted in `var/data/self_registration.json`
- Approved dynamic login users are written to `config/packages/security_dynamic_users.yaml`
- Some portal/business flows are still demo-oriented and use mock data

## Development Notes

- Static files are served through `public/router.php`
- Symfony web profiler toolbar is disabled in dev for cleaner UI testing
- Billing and portal features have been updated beyond the original 2020 scope of the project
