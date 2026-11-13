# cPanel Deployment Notes

**Status:** Environment-specific capabilities unverified. Do not treat this as deployment approval.

## Verified locally

- Windows 11 development environment: PHP 8.4.24, Composer 2.10.2, Node 24.19.0, npm 11.17.0.
- PHP extensions include `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `filter`, `hash`, `intl`, `mbstring`, `openssl`, `PDO`, `pdo_mysql`, `session`, `tokenizer`, and `xml`; this confirms local CLI modules only, not cPanel runtime compatibility.
- One configured Laravel MySQL PDO authentication attempt to `127.0.0.1:3306` failed with authentication denied. No SQL was issued. The configured database name is blank; no local DB identity or owner intent established.
- Browser runner is not declared in project npm manifests and no browser executable was found on `PATH`; browser flows were not run. No package was installed.
- No project Preline dependencies, MCP configuration, or required Preline skill directories found. MCP authentication was not attempted; bearer token, skills, and entitlement remain unverified/unavailable locally.
- Git reports unborn `main`, all intended repository files untracked, no refs, and zero objects. `.env`, `vendor`, `node_modules`, generated build/cache/runtime data are ignored. No cPanel account, hosting panel, deployed app, or host runtime inspected.

## Not verified with hosting provider

- Provider, hosting plan/account, target server, and assigned domain.
- Host PHP version and required Laravel 13 extensions; Composer availability and approved dependency-install workflow.
- SSH/Terminal access and Composer execution method.
- Node/npm availability or approved build path on host. Prefer build locally/CI and upload only `public/build` output plus source/vendor per approved deployment process.
- Document root path and whether it can point to Laravel `public/`; whether symlinks and `.htaccess` rewrites are enabled; whether application source can live outside web root.
- MariaDB version, dedicated database name/user, allowed host, least-privilege provisioning, and backup/restore process.
- Cron availability and minimum frequency; persistent queue-worker process/daemon option.
- Writable storage paths and permissions, HTTPS availability/canonical domain, server-side environment injection, and release rollback.
- Reverse proxy/CDN presence, trusted proxy addresses, and forwarded-header behavior.

## Required deployment shape

1. Keep application source, `.env`, `vendor`, and writable runtime directories outside public document root where host permits. Expose only Laravel `public/` as web root. If cPanel forces a different document root, obtain security review of exact rewrite and directory layout before deploy. Never expose `.env`, `vendor`, `storage`, or framework source via HTTP.
2. Provision a dedicated least-privilege MariaDB database/user through approved operator process. Populate real credentials only in server-side environment settings. Do not reuse local or production credentials in repository files.
3. Confirm PHP version/extensions before install. Resolve Composer dependencies with committed lockfile, production flags and no development dependencies only after reviewing Composer security advisories. Do not run dependency updates on production.
4. Build frontend assets in a controlled build environment with Node/npm and upload build artifacts. Do not assume npm exists on cPanel.
5. Configure `APP_ENV=production`, `APP_DEBUG=false`, canonical `APP_URL`, generated unique `APP_KEY`, `APP_LOCALE=id`, `APP_TIMEZONE=Asia/Jakarta`, secure session cookie over HTTPS, and secret configuration outside public root. No example key is supplied.
6. Ensure storage and bootstrap/cache writable by the PHP process with minimum needed permissions. Verify logs, file uploads only when implemented, and backup/restore before traffic.
7. Database queue/session/cache configuration depends on Laravel foundation migrations being applied to the new application's database. `QUEUE_CONNECTION=database` needs a persistent worker for asynchronous throughput; verify cPanel daemon/cron support. For limited hosting, use cron to invoke `php artisan queue:work --stop-when-empty --max-time=50` at an approved interval, understanding latency and overlap constraints. Configure scheduler with `* * * * * php /path/to/app/artisan schedule:run >> /dev/null 2>&1` only if scheduled tasks are later added. Worker/cron support unverified.
8. Deploy using a release directory or verified backup. Apply migrations only to the dedicated target DB after backup and operator approval. Roll back by restoring prior code release and compatible DB backup; destructive `migrate:rollback` is not a substitute for backup. Production deployment and migrations were not performed.

## Authentication status

- Admin login and logout routes are implemented. Login uses normalized-email throttling (five failed attempts per 60 seconds) plus an IP-wide spray limit (30 failed attempts per 60 seconds). Failed credentials and throttled attempts return the same generic credential error to avoid account enumeration.
- `php artisan admin:create` bootstrap command is implemented and refuses to create a second account. No admin account has been created for the target hosting account.
- cPanel proxy configuration is unverified. Laravel currently uses its resolved request IP; forwarded headers must not be trusted until provider proxy addresses and header behavior are confirmed and configured.
- Host authentication behavior, HTTPS/session settings, and database-backed cache/rate-limit persistence remain unverified.

## Exact provider/account inputs still required

Obtain and verify before deployment:

- Provider, hosting plan/account, target server, assigned domain, and exact document root.
- Host PHP version and required Laravel extensions; SSH/Terminal availability and Composer access/workflow; whether application source can live outside web root.
- MariaDB version, dedicated database name, database username, allowed host, and least-privilege provisioning confirmation. Supply password only through secure host configuration.
- Cron availability and minimum supported frequency; persistent queue worker process/daemon option.
- Approved Node build path (host or CI/local artifact build and upload).
- HTTPS availability, canonical domain, and server-side environment injection for `APP_KEY`, DB credentials, session/cache settings, and other secrets.
- Reverse proxy/CDN presence, exact trusted proxy IP ranges, forwarded headers, and whether direct origin access is blocked.
- Storage symlink support, writable paths/permissions, persistent cache backend, backup schedule, restore procedure, and rollback procedure.
- Approved admin name, normalized email, and initial password delivery/rotation procedure. Provide bootstrap credentials only through secure host environment configuration, never repository or deployment notes.

Preline Theme Generator/MCP setup remains blocked. Do not release this foundation publicly as a completed business website or usable admin system.

## Release checklist

- [ ] Provider confirms Laravel 13 runtime, PHP extensions, web root, HTTPS, MariaDB, cron/worker, storage, backups.
- [ ] Dedicated database and unique production secrets configured outside repository.
- [ ] Auth bootstrap/recovery policy approved; provider proxy trust, limiter persistence, and secure credential inputs verified.
- [ ] Preline skills/MCP/theme workflow completed and generated Tailwind v4 output verified.
- [ ] Build, tests, browser navigation, mobile, error handling, and security review pass.
- [ ] Backup and rollback rehearsal completed.
- [ ] Explicit production deployment approval received.
