# City School

City School is a Symfony-based school management and portal application with separate experiences for students, teachers, parents, admins, and super admins.

## Local Run

Start the app from the project root:

```powershell
php -d max_execution_time=300 -S localhost:8000 -t public public/router.php
```

Open:

```text
http://localhost:8000
```

## Demo Logins

- Student: `student` / `student123`
- Teacher: `teacher` / `student123`
- Admin: `admin` / `student123`
- Parent: `parent` / `student123`
- Super Admin: `superadmin` / `student123`

## Main Features

- Public website pages for home, courses, events, pricing, contact, and admissions
- Student, teacher, parent, admin, and super-admin portal dashboards
- Admin billing and subscription management
- Stripe card and PayPal provider selection in billing UI
- Self-registration request flow for students and parents
- Admin approval workflow for pending registrations
- Student impersonation from Admin User Management
- Mailer test action from admin billing page

## Registration Flow

### Student

- Registration page: `/portal/register`
- Login page: `/portal/login`
- Requests are submitted for admin approval before login is allowed

### Parent

- Registration page: `/parent/register`
- Login page: `/parent/login`
- Requests are submitted for admin approval before login is allowed

### Admin Approval

- Open `/admin/users`
- Review `Pending Self-Registration Requests`
- Approve or reject requests
- Approved requests appear in `Approved Self-Registrations`

## Billing and Subscription

Admin billing page:

```text
/admin/billing-subscription
```

Supported payment provider options in the UI:

- Stripe Card
- PayPal

Additional billing features:

- Plan changes
- Renewal mode switching
- Manual renewal
- Failed payment simulation
- Mailer connectivity test

## Email / Gmail

Mailer is controlled by `MAILER_DSN`.

Example Gmail configuration for `.env.local`:

```dotenv
MAILER_DSN=smtp://YOUR_GMAIL%40gmail.com:YOUR_APP_PASSWORD@smtp.gmail.com:587?encryption=tls&auth_mode=login
```

Use a Gmail App Password, not your normal Gmail password.

After updating mail settings:

```powershell
php bin/console cache:clear
```

## Notes

- Static assets are served correctly through `public/router.php`
- Approved self-registrations are persisted to `var/data/self_registration.json`
- Dynamic approved login users are written to `config/packages/security_dynamic_users.yaml`
