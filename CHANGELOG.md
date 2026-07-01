# Changelog

## 2026-07-01

### Added

- Student self-registration page at `/portal/register`
- Parent self-registration page at `/parent/register`
- Admin approval and rejection flow for self-registration requests
- Approved self-registration tracking in Admin User Management
- Persistent registration request storage in `var/data/self_registration.json`
- Dynamic approved-user login sync through `config/packages/security_dynamic_users.yaml`
- Admin student impersonation action from `/admin/users`
- PayPal option in admin billing and subscription flow
- Dynamic Stripe/PayPal field toggling in billing payment method form
- Admin billing mailer connectivity test action

### Changed

- Disabled Symfony web profiler toolbar in dev
- Fixed static asset serving through `public/router.php`
- Fixed teacher login page branding and icon rendering
- Fixed parent dashboard double-offset layout issue
- Added return-to-homepage links on login pages
- Replaced unsupported icon usage and added fallback icon aliases for bundled Font Awesome subset

### Verified

- Public pages render with CSS and images
- Student, teacher, admin, parent, and super-admin portal pages load correctly
- Admin billing page returns HTTP 200 after payment and mailer updates
- Admin users page returns HTTP 200 after registration approval and impersonation updates