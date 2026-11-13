# Architecture: Foundation

**Scope:** Phase 1 Laravel foundation only. No business-domain schema, content modules, analytics, leads, notifications, or commerce.

## Design Read

Reading this as: public service-company shell and authenticated admin boundary for business operators, in the restrained technical style defined by [`docs/DESIGN.md`](DESIGN.md), dial ENERGY 1 / RHYTHM 1 / MOTION 1. Navy and blue establish documented brand hierarchy, white and light surfaces preserve readability, compact single-column content serves the empty foundation state. System sans fallback keeps text readable offline; Montserrat is declared as preferred only, no remote font source assumed. No decorative icons, imagery, or motion. Provisional tokens mirror documented brand values until Preline Theme Generator is available.

## Runtime Boundaries

- Laravel skeleton `13.11.0`, framework resolved as `13.40.0`, PHP constraint `^8.3`.
- MariaDB configured as default connection. Local TCP reachable, DB credentials/database not supplied. Do not run migrations until an isolated, disposable DB is confirmed.
- Laravel's default `users`, cache, and queue migrations are retained as framework foundation only. No migration executed against MariaDB; no business entities introduced.
- Sessions, cache, and queues default to database drivers. This requires the framework migrations to be applied to the application's own DB during an approved local/deployment setup. Hosting queue-worker support remains unverified; scheduler can use documented cron fallback.
- Authentication uses Laravel's native session guard, CSRF-protected login/logout routes, session ID regeneration at login, session invalidation and CSRF token regeneration at logout, and per-email/IP throttling. `/admin` is protected; registration and default accounts do not exist.
- `admin:create` is a CLI-only, one-time account bootstrap. It requires temporary `ADMIN_BOOTSTRAP_NAME`, `ADMIN_BOOTSTRAP_EMAIL`, and `ADMIN_BOOTSTRAP_PASSWORD` values, validates password complexity, hashes through the `User` model, rechecks inside a transaction, and uses a MariaDB advisory lock to serialize concurrent bootstrap attempts.

## Phase 3 — Content Management (Backend)

Phase 3 adds the content-management data model and backend structure. No UI, no Preline, no Phase 4 modules.

### Data Model

Content tables created in [`database/migrations/2026_01_01_000001_create_content_tables.php`](database/migrations/2026_01_01_000001_create_content_tables.php), ordered by dependency. `users` table extended with `role` column (default `admin`).

**Taxonomies:**

| Table | Purpose |
|-------|---------|
| `product_brands` | Preserved product brands (8 seeded) |
| `package_brands` | Package-specific brand taxonomy |
| `package_categories` | Package category taxonomy (pivot to `packages`) |
| `package_usage_categories` | Usage-scenario taxonomy (pivot to `packages`) |
| `blog_categories` | Blog category taxonomy |
| `blog_tags` | Blog tag taxonomy |

**Content:**

| Table | Key fields |
|-------|-----------|
| `products` | `brand_id`, `name`, `slug`, `model`, `camera_type`, `key_features` (json), `technical_specs` (json), `recommended_applications` (json), `image`, `gallery` (json), `published` |
| `packages` | `brand_id`, `title`, `slug`, `description`, `hero_image`, `camera_quantity` (tinyint), `included_equipment` (json), `installation_scope` (json), `relevant_specs` (json), `installation_process` (json), `exclusions` (json), `price` (decimal, nullable), `published` |
| `services` | `type` (installation/repair/product_only), `title`, `slug`, `description`, `scope` (json), `applications` (json), `process` (json), `published` |
| `blog_articles` | `user_id`, `blog_category_id`, `title`, `slug`, `excerpt`, `body`, `featured_image`, `status` (draft/published/archived), `published_at`, `scheduled_for`, `reading_time`, `published` (denormalized) |
| `faqs` | `context_type`/`context_id` (polymorphic optional parent), `question`, `answer`, `sort_order`, `published` |
| `pages` | `title`, `slug`, `body`, `published` |
| `media_assets` | `file_path`, `disk`, `type`, `alt_text`, `size`, `width`, `height`, `mime_type`, `created_by` |

**SEO:**

| Table | Key fields |
|-------|-----------|
| `seo_metadata` | Polymorphic `seoable` (morphs), `seo_title`, `meta_description`, `canonical`, `robots`, `og_title`, `og_description`, `og_image`, `og_type`, `twitter_card`, `twitter_title`, `twitter_description`, `twitter_image` |

### Models

All content models in [`app/Models/`](app/Models/):

- `ProductBrand`, `Product`, `PackageBrand`, `Package`, `PackageCategory`, `PackageUsageCategory`, `Service`, `BlogCategory`, `BlogTag`, `BlogArticle`, `Faq`, `SeoMetadata`, `MediaAsset`, `Page`.
- `HasSlugs` trait: auto-generates unique `slug` from `title`/`name` on create/update; manual override accepted.
- `published()` query scope on all public-facing models.
- Polymorphic `seo()` morphOne on `Product`, `Package`, `Service`, `BlogArticle`, `Page`.
- Polymorphic `context()` morphTo on `Faq`.

### SEO Resolution

[`SeoMetadataResolver`](app/Services/SeoMetadataResolver.php) centralizes metadata resolution with fallback chain:

1. Explicit `SeoMetadata` record (via `seo()` relation)
2. Model's own attributes (`title`, `name`, `description`, `excerpt`, `image`, `hero_image`, `featured_image`)
3. Caller-provided defaults
4. Global config defaults from [`config/seo.php`](config/seo.php)

Absolute URLs via `url()`/`asset()`.

### Routes

**Public** ([`routes/web.php`](routes/web.php)):

- `GET /products`, `GET /products/{slug}`
- `GET /packages`, `GET /packages/{slug}`
- `GET /services`, `GET /services/{slug}`
- `GET /blog`, `GET /blog/{slug}`
- `GET /faq`
- `GET /pages/{slug}`

All public routes render only `published` content. Controllers in [`app/Http/Controllers/Public/`](app/Http/Controllers/Public/).

**Admin** (behind `auth` + `can:admin` gate):

- CRUD JSON routes for: products, packages, package-categories, package-usage-categories, services, blog (articles), blog-categories, blog-tags, faqs, seo, media, pages, product-brands, package-brands.
- Controllers in [`app/Http/Controllers/Admin/`](app/Http/Controllers/Admin/).
- Form requests in [`app/Http/Requests/Admin/`](app/Http/Requests/Admin/).
- Gate `admin` registered in [`AppServiceProvider::boot()`](app/Providers/AppServiceProvider.php): `user->role === 'admin'`.

### Authorization

- `users.role` string column, default `admin`.
- Native `Gate::define('admin', ...)` in [`AppServiceProvider`](app/Providers/AppServiceProvider.php).
- All admin routes behind `middleware('can:admin')`.
- Guest → 302 to `/login`; non-admin user → 403; admin → 200.

### Factories and Seeders

- 13 content factories in [`database/factories/`](database/factories/).
- [`ContentSeeder`](database/seeders/ContentSeeder.php) seeds: 8 preserved product/package brands, 3 service types (draft), package categories, usage categories, blog content (draft only), FAQs (unpublished), pages (unpublished).
- No default admin credentials. No invented prices, specs, or warranties.

### Test Coverage

| Test file | Scope |
|-----------|-------|
| `ContentManagementTest` | Admin CRUD, validation, authorization (403/401/200/201/422) |
| `PublicContentTest` | Published/draft visibility, slug resolution, SEO fallback chain |
| `SlugGenerationTest` | Auto-slug generation, uniqueness, manual override |
| `ModelRelationshipTest` | All Eloquent relationships and polymorphic relations |

All tests run under in-memory SQLite via `RefreshDatabase`. No MariaDB connection required.

## Frontend

- Vite with Tailwind CSS v4, Turbo Drive and Stimulus.
- Verified maintained `hotwired-laravel/turbo-laravel` `2.6.0` supports Illuminate 13; middleware auto-registration defaults on. `@hotwired/turbo` and `@hotwired/stimulus` are installed from npm.
- Navigation controller is scoped to the mobile menu and removes its document key handler in `disconnect`. No unverified Preline APIs are initialized.
- Tailwind tokens are provisional copies of `DESIGN.md`, not generated output. No Montserrat download or remote font runtime assumption.
- Preline skills (`preline-theme-generator`, `preline-mcp`), authenticated MCP integration, Theme Generator output, entitlement, and interactive component documentation were unavailable. Therefore no Preline component/block, package, or API is claimed integrated. This is a blocking Phase 2 foundation gap.

## Quality Dials and Reasons

- ENERGY 1: quiet service-business foundation, not marketing composition.
- RHYTHM 1: single-purpose shell pages, no fabricated modules.
- MOTION 1: only native hover/focus and functional menu state; avoid unnecessary movement.
- Focal point: page title and one honest state message.
- Palette: documented navy and blue with documented light background; accent limited to keyboard focus.
- One repeated identity motif: narrow blue rule beside public page identity.
- All controls have real routes or menu behavior. Public navigation includes only existing home destination.

## Admin Bootstrap Procedure

After an approved environment has the existing framework migrations applied, inject temporary process environment variables, not `.env` or Laravel config values: `ADMIN_BOOTSTRAP_NAME`, `ADMIN_BOOTSTRAP_EMAIL`, and `ADMIN_BOOTSTRAP_PASSWORD`. This avoids persistence in cached config. Password must be at least 12 characters and contain lowercase, uppercase, numeric, and symbol characters. Run `php artisan admin:create` once from the application root using the intended database connection. The command refuses missing/invalid input and refuses creation when any user exists. It prints no credential values. Immediately unset variables from the shell/process environment and remove temporary secret files or deployment overrides. Do not put values in committed files or use a public route. `DatabaseSeeder` creates no accounts.

## Tests and Safety

Feature tests cover public rendering, login success and invalid/throttled attempts, intended redirect, guest/admin access, logout session invalidation, absence of registration, bootstrap success/refusal/missing credentials, health route, core environment and Turbo/Stimulus frontend source. Phase 3 adds content management tests: admin CRUD validation and authorization, public published/draft visibility, slug generation and uniqueness, SEO metadata fallback chain, and model relationship integrity. PHPUnit uses in-memory SQLite. No test points at MariaDB. Run PHP tests and Vite build before considering foundation locally runnable.

## Open Blockers

- Admin account recovery and additional account provisioning policy. No public registration, reset workflow, or full 5-role matrix is implemented. Single `admin` role used for now; role matrix deferred to Phase 4.
- Preline skills, token, MCP access, entitlement and theme generation.
- Actual cPanel PHP version, document-root configuration, MariaDB credentials, cron, queue worker, storage symlinks, HTTPS, backup, and rollback capability.
- Exact package maintenance/security review at release time.
