# Kinyinya Jobs — PHP + MySQL Dashboard

A multi-role dashboard for the Job Matching and Recruitment Platform (Umurenge Kinyinya, Gasabo District), built in plain PHP with a real MySQL database. Three roles share one codebase:

- **Job seeker** — browse/search/filter jobs, quick-apply, track application status
- **Employer** — post vacancies (pending admin approval), see applicants per posting, update application status
- **Admin** — review and approve/reject new postings, platform-wide stats (users, postings, applications by status/category)

See the [role-based user guide](USER_GUIDE.md) for step-by-step instructions for job seekers, employers, and administrators.

Everything — employers, seekers, jobs, applications, messages, and notifications — is stored in MySQL. `$_SESSION` is used only for the signed-in user and one-off flash messages. Every change (posting a job, applying, changing a status, approving a posting) is written to the database and remains available across restarts and browsers.

## Two rules baked into the workflow

- **Admin approval** — a new job posting starts as `pending` and is invisible to job seekers until an admin approves it from the Pending approvals queue on the admin dashboard.
- **Automatic expiration** — every posting has a `deadline`. On every request, the app runs `UPDATE jobs SET active = 0 WHERE active = 1 AND deadline < CURDATE()`, so postings close themselves once their deadline passes — no manual step required. (For a posting to close even when nobody is browsing the site, point a daily cron job at the same query — see `kj_expire_jobs()` in `includes/data.php`.)

## Set it up

### Recommended automatic setup

No source-code edits are required.

- **PHP + MySQL Workbench/MySQL Server:** run `php -S localhost:8000`, open **http://localhost:8000/setup.php**, and enter the MySQL administrator credentials used by Workbench.
- **XAMPP:** copy the folder to `C:\xampp\htdocs\kinyinya-jobs`, start Apache and MySQL, then open **http://localhost/kinyinya-jobs/setup.php**. XAMPP commonly uses `root` with an empty password.

The installer creates the schema, a dedicated application account, optional demo data, and `includes/local-config.php`. URLs are detected automatically, so the application works at the web root or inside any `htdocs` subfolder. Database environment variables remain supported and take precedence over the generated local configuration.

### Manual setup (optional)

You need **PHP 8+ with the `pdo_mysql` extension** and a **MySQL or MariaDB server**.

1. **Create the database and tables:**
   ```bash
   mysql -u root -p < database/schema.sql
   ```
2. **Create a dedicated app database user** (most MySQL/MariaDB installs won't let `root` connect over TCP with a password, which is what PHP's PDO driver needs — this avoids that and is better practice anyway):
   ```bash
   mysql -u root -p < database/create_user.sql
   ```
   This creates `kinyinya_app` / `kinyinya_dev_password` with access to the `kinyinya_jobs` database. Change the password in that file (and in `includes/config.php` to match) before deploying anywhere but your own machine.
3. **Load demo data** (optional, but the app is much more useful to look at with it):
   ```bash
   mysql -u root -p kinyinya_jobs < database/seed.sql
   ```
4. **Check `includes/config.php`** matches your setup — host, port, database name, user, password. Every value can also be set via an environment variable of the same name instead of editing the file.
5. **Run the app:**
   ```bash
   php -S localhost:8000
   ```

Then open **http://localhost:8000** and sign in with an account created during registration or with one of the optional seeded accounts. If you skipped seeding, simply create a seeker or employer account from the registration page.

## File structure

```
index.php              Landing page + email/password login
register.php           Job-seeker and employer registration
login.php / logout.php Session authentication
database/
  schema.sql            Table definitions (users, profiles, jobs, applications, messages, notifications)
  seed.sql               Demo data matching the original in-memory version
  create_user.sql         Creates the dedicated kinyinya_app MySQL user
includes/
  config.php             DB host/name/user/password (edit this, or set env vars)
  db.php                  PDO connection helper
  data.php                All kj_*() data-access functions — the only file that runs SQL
  header.php, footer.php Shared layout/nav
assets/style.css        Design system (deep green / ochre civic palette)
seeker/
  dashboard.php          Application status tracker
  jobs.php                Search, filter, quick-apply
employer/
  dashboard.php           Postings overview + applicant counts, approval/expiry status
  post-job.php            Simple job posting form (submits as 'pending')
  applicants.php           Review applicants, change status
admin/
  dashboard.php           Pending-approval queue, platform-wide stats and tables
tests/
  smoke.php               Dependency-free database and security smoke checks
```

## Demonstration accounts

- Job seeker: `eric@example.com` / `password123`
- Employer: `techhub@example.com` / `password123`
- Administrator: `admin@kinyinya.rw` / `Admin123!`

Passwords are stored using PHP password hashes, and successful sign-in creates a role-based PHP session. Job seekers and employers can create their own accounts from `register.php`.

Job seekers can complete their profile after registration and optionally upload, replace, or remove one PDF, DOC, or DOCX CV (up to 5 MB). Employers can view an uploaded CV only from an application they are authorized to review.

## Verification

Run the dependency-free smoke checks from the project root:

```bash
php tests/smoke.php
```

The checks verify the schema, password hashing, CSRF support, job visibility and approval rules, edit reapproval, deadline enforcement, and duplicate-application protection. They work with or without demonstration data and roll back their temporary workflow records.
