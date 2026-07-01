# City School

City School is a Symfony-based school management and education portal platform with separate experiences for students, teachers, parents, admins, and super admins.

## Overview

The application includes:

- Public marketing pages for home, courses, values, events, admissions, pricing, and contact
- Student, teacher, parent, admin, and super-admin dashboards
- Admin user management, billing, scheduling, attendance, and academic management
- Subscription onboarding and trial flows
- Student and parent self-registration request flows with admin approval
- Stripe card and PayPal selection in billing UI
- Test email action for verifying mail delivery from the billing page

## Requirements

- PHP 8.1+
- Composer dependencies installed

## Run Locally

From the project root, start the PHP server with the static-file router:

```powershell
php -d max_execution_time=300 -S localhost:8000 -t public public/router.php
```

Open the app at:

```text
http://localhost:8000
```

## Demo Accounts

- Student: `student` / `student123`
- Teacher: `teacher` / `student123`
- Admin: `admin` / `student123`
- Parent: `parent` / `student123`
- Super Admin: `superadmin` / `student123`

## Main Routes

- Home: `/`
- Student login: `/portal/login`
- Teacher login: `/teacher/login`
- Admin login: `/admin/login`
- Parent login: `/parent/login`
- Super Admin login: `/super-admin/login`
- Admissions: `/admissions`
- Admin billing: `/admin/billing-subscription`
- Admin users: `/admin/users`

## Self-Registration Flow

### Student registration

- Request page: `/portal/register`
- Users submit a registration request for admin approval
- Approved users become available in Admin User Management

### Parent registration

- Request page: `/parent/register`
- Users submit a registration request for admin approval
- Approved users become available in Admin User Management

### Admin approval

Open `/admin/users` and review:

- `Pending Self-Registration Requests`
- `Approved Self-Registrations`

Admins can:

- Approve requests
- Reject requests
- Impersonate student accounts from the users table

## Billing and Subscription

The admin billing page supports:

- Subscription overview
- Plan changes
- Renewal settings
- Manual renew now flow
- Payment failure simulation
- Payment method update
- Mailer connectivity test

Supported payment provider options in the UI:

- Stripe Card
- PayPal

## Email / Gmail Setup

Mailer is configured through `MAILER_DSN`.

Create `.env.local` and add a Gmail SMTP DSN like this:

```dotenv
MAILER_DSN=smtp://YOUR_GMAIL%40gmail.com:YOUR_APP_PASSWORD@smtp.gmail.com:587?encryption=tls&auth_mode=login
```

Notes:

- Use a Gmail App Password, not your regular Gmail password
- Gmail requires 2-Step Verification for App Passwords

After updating mail settings, clear the Symfony cache:

```powershell
php bin/console cache:clear
```

## Persistence Notes

- Self-registration requests are stored in `var/data/self_registration.json`
- Approved dynamic login users are written to `config/packages/security_dynamic_users.yaml`

## Known Limitations

- A large part of the portal data is mock/demo data rendered from controller arrays
- Billing flows are UI-driven demo flows, not full production payment processing
- Dynamic approved users are stored through Symfony memory-user config rather than a full database-backed user entity
- Student impersonation uses the current demo authentication setup

## Project Structure

- `src/Controller/` application controllers
- `templates/` Twig templates for public pages and portals
- `public/` static assets and router entrypoint
- `config/` Symfony configuration
- `var/` cache, logs, and persisted registration request data

## Status

This project is actively being extended and refined as a demo-style multi-portal school management system.
