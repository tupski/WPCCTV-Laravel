# WP CCTV Indonesia

Laravel foundation for WP CCTV Indonesia, a CCTV installation and repair service website. This is an early application foundation, not a complete public site or business dashboard.

## Stack constraints

Values below are dependency constraints from project manifests, not guarantees about versions installed elsewhere:

- PHP `^8.3`
- Laravel Framework `^13.17`
- `hotwired-laravel/turbo-laravel` `^2.6`
- Tailwind CSS `^4.0`
- Vite `^8.0`
- Turbo `^8.0`
- Stimulus `^3.2`
- MariaDB connection configured as `mariadb`

Resolved versions are recorded in `composer.lock` and `package-lock.json`. Check local PHP, Composer, Node.js, npm, and database compatibility before setup.

## Local foundation setup

Requires PHP, Composer, Node.js, and npm. Copy `.env.example` to `.env`, set a local application key, then install dependencies:

```sh
composer install
copy .env.example .env
php artisan key:generate
npm ci
npm run build
```

`copy` is Windows Command Prompt syntax. In PowerShell use `Copy-Item .env.example .env`; on macOS/Linux use `cp .env.example .env`.

Set local database values in `.env` only when credentials are verified. No database credentials are supplied here. Review Composer scripts before running them: `composer setup` invokes migrations. Tests use in-memory SQLite per `phpunit.xml`; they do not verify MariaDB connectivity or schema.

Start the local server and asset watcher in separate terminals:

```sh
php artisan serve
npm run dev
```

## Checks

```sh
composer validate --no-check-publish
composer audit
php artisan test
npm run build
```

## Current limitations

- Preline UI, MCP integration, required skills, and generated brand theme remain unavailable. Existing styling is provisional and does not satisfy the documented Preline requirement.
- Admin session authentication and one-time CLI bootstrap exist. Bootstrap credentials must be injected into process environment only, not `.env` or Laravel config, to prevent persistence in cached configuration. Public registration, password recovery, account provisioning after first setup, roles, and permissions are not in scope.
- MariaDB credentials and cPanel capabilities remain unverified.
- This foundation does not establish deployment readiness or completed business features.

## Initial admin account

Once the intended database is prepared through the approved deployment setup, inject `ADMIN_BOOTSTRAP_NAME`, `ADMIN_BOOTSTRAP_EMAIL`, and `ADMIN_BOOTSTRAP_PASSWORD` into the command process temporarily. Use a password of at least 12 characters with lowercase, uppercase, number, and symbol. Run `php artisan admin:create` from the application root. Command refuses creation if any user exists and does not print credentials. Unset all three variables immediately afterward, including shell exports, temporary env files, or deployment-level overrides. Never commit credentials or seed accounts. Full procedure and safety behavior are documented in [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md#admin-bootstrap-procedure).

See [`AGENTS.md`](AGENTS.md), [`docs/PRD.md`](docs/PRD.md), [`docs/DESIGN.md`](docs/DESIGN.md), [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md), and [`docs/deployment/CPANEL.md`](docs/deployment/CPANEL.md).

## Non-production caution

Keep this foundation in local development. Do not deploy it, use production credentials, or run production migrations. Deployment requires verified hosting and database configuration, completed authentication and authorization, the required Preline workflow, and feature and security acceptance evidence.
