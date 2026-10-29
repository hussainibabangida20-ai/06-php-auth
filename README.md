# SWD Auth

A PHP 8 and MySQL authentication example intended for XAMPP local development.

## Local setup

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Import [`database/schema.sql`](database/schema.sql) in phpMyAdmin.
3. Configure the environment variables for the Apache process:

   In an Apache virtual-host configuration, these can be set with `SetEnv`
   directives (or configured in the environment used to launch Apache):

   ```apache
   SetEnv SWD_ENV development
   SetEnv SWD_DB_HOST 127.0.0.1
   SetEnv SWD_DB_PORT 3306
   SetEnv SWD_DB_NAME swd_auth
   SetEnv SWD_DB_USER root
   SetEnv SWD_DB_PASS ""
   ```

   Restart Apache after configuring them. The local development configuration
   permits XAMPP's default `root` account; do not use that account for a
   deployed application.
4. Visit `http://localhost/swd/06-php-auth/register.php`.

The app defaults to production mode when `SWD_ENV` is not set. Production mode
requires an HTTPS base URL and a non-root MySQL account with a non-empty
password, and redirects insecure HTTP requests to that HTTPS URL. Configure
Apache or the public reverse proxy to enforce HTTPS before requests reach PHP;
an application redirect cannot protect credentials already sent over HTTP. It
does not silently fall back to the XAMPP root account.

For production, configure:

```text
SWD_ENV=production
SWD_BASE_URL=https://your-domain.example
SWD_DB_HOST=...
SWD_DB_PORT=3306
SWD_DB_NAME=swd_auth
SWD_DB_USER=a-least-privilege-database-user
SWD_DB_PASS=...
```

HTTPS is detected from the web server's `HTTPS` variable. If TLS terminates at
a trusted reverse proxy, set `SWD_HTTPS=1` in the application process only
when the proxy-to-application setup is trusted and the public site is HTTPS.
This enables the secure session cookie and HSTS handling. Set `SWD_DEBUG=1`
only in development when browser-visible PHP errors are specifically needed;
errors are otherwise logged and not displayed.

## Authentication and security

- Passwords are stored with PHP `password_hash()` and checked with
  `password_verify()`. They are never stored as plain text or with MD5/SHA-1.
- Registration and profile updates validate input on the server and use
  prepared PDO statements. The unique database email constraint handles
  concurrent duplicate registrations/updates.
- The profile page updates name/email and provides a separate password change
  flow requiring the current password; password changes renew the session ID.
- Forms that change state use session-bound CSRF tokens.
- Login regenerates the session ID, rehashes outdated password hashes, and
  throttles failed attempts by email and IP (five failures in 15 minutes).
- Sessions use strict mode, HTTP-only and SameSite=Lax cookies, and secure
  cookies on HTTPS. Authenticated sessions expire after 30 minutes idle.
- Intended login destinations are validated as same-site relative paths.
- Admin routes check the user's current database role. User role changes
  require CSRF validation and cannot demote the acting admin.
- Production responses set X-Frame-Options, X-Content-Type-Options,
  Referrer-Policy, Content-Security-Policy, cache-control protections, and
  HSTS on HTTPS. Production HTTP requests redirect to the configured HTTPS
  base URL.
- User-controlled output is HTML-escaped. SQL values are parameterized.

### First administrator

Create the account through registration, then run the promotion utility from
the project directory in a terminal configured with the same database
environment variables as Apache:

```powershell
C:\xampp\php\php.exe promote-admin.php me@example.com
```

The utility exits when run outside the PHP CLI.

## Security review findings addressed

| Severity | Finding | Resolution |
|---|---|---|
| Medium | PHP errors were displayed on all requests, potentially exposing implementation details. | Error display is disabled by default; enable it only with `SWD_DEBUG=1` in development. Errors are logged. |
| Medium | HTTP/proxy deployments could expose login traffic or session cookies; HSTS was absent. | Production requires an HTTPS base URL, redirects HTTP requests, secures session cookies, and emits HSTS on HTTPS. Trusted TLS proxies must explicitly set `SWD_HTTPS=1`. |
| Medium | Missing database environment variables silently selected MySQL root with an empty password. | Database credentials have no root/empty fallback. Production requires an explicitly configured non-root account and non-empty password. XAMPP root is permitted only in explicit development mode. |

The audit found no actionable SQL/command injection, XSS, CSRF, open-redirect,
broken access-control, or password-storage findings in the reviewed project.
