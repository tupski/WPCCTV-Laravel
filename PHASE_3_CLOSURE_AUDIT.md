# Phase 3 Closure Audit

## Executive Verdict

**PASS WITH BLOCKERS**

Phase 3 content-management backend is functionally verified: full test suite passes (67/67, 207 assertions, exit 0), every public controller enforces the `published()` scope, all admin routes are gated by `auth` + `can:admin`, and all four static-analysis test-coverage gaps (draft-404, slug-collision, SEO-fallback, 403-for-non-admin) have dedicated tests in place. No Critical findings exist. Three High findings remain: a TOCTOU race in `HasSlugs` that can surface as an unhandled 500 under concurrent admin saves, a missing `canonical` default in `config/seo.php` that can emit an empty `<link rel="canonical">` tag, and an N+1 in `MediaAssetController::index()`. Four Medium findings concern data-integrity edge cases (author spoofing on blog articles, `Faq.published` default asymmetry, missing unique index on `services.type`, and a dead-code fallback in `UpdateBlogArticleRequest`). None compromise the published-content or admin-authorization boundaries. Phase 4 may begin after H1–H3 are remediated; MariaDB runtime verification is a deployment gate, not a development blocker.

## Verified Implementation

| Area | Verified | Evidence |
|------|----------|----------|
| 17 content tables, FK integrity | All FKs `constrained()`, cascade/nullOnDelete correct; composite PKs on pivots; `unique(['seoable_type','seoable_id'])` on `seo_metadata` | [`database/migrations/2026_01_01_000001_create_content_tables.php`](database/migrations/2026_01_01_000001_create_content_tables.php#L31-L252) |
| `users.role` column | `string(50)`, default `admin`, `NOT NULL` | [`database/migrations/2026_01_01_000000_add_role_to_users_table.php`](database/migrations/2026_01_01_000000_add_role_to_users_table.php#L15) |
| Admin route gating | All `admin` routes inside `Route::middleware('auth')` + `->middleware('can:admin')` group | [`routes/web.php`](routes/web.php#L62-L156) |
| Gate definition | `Gate::define('admin', fn($user) => $user->role === 'admin')` | [`app/Providers/AppServiceProvider.php`](app/Providers/AppServiceProvider.php#L24-L26) |
| Form request `authorize()` | Every admin form request calls `$this->user()->can('admin')` | [`app/Http/Requests/Admin/StoreProductRequest.php`](app/Http/Requests/Admin/StoreProductRequest.php#L11-L14) |
| Mass-assignment hygiene | `$fillable` arrays on all models; admin controllers use `$request->safe()` | [`app/Models/Product.php`](app/Models/Product.php#L16-L20) |
| Public draft filtering | `Product`, `Package`, `Service`, `BlogArticle`, `Page`, `Faq` all expose `scopePublished`; public controllers call it | [`app/Http/Controllers/Public/ProductController.php`](app/Http/Controllers/Public/ProductController.php#L41-L44) |
| `HasSlugs` auto-generation | Slug generated on create if empty; on update only when slug not dirty | [`app/Models/HasSlugs.php`](app/Models/HasSlugs.php#L17-L49) |
| `SeoMetadataResolver` 4-tier fallback | Explicit `SeoMetadata` → model attributes → caller defaults → `config/seo.php` | [`app/Services/SeoMetadataResolver.php`](app/Services/SeoMetadataResolver.php#L25-L81) |
| `config/seo.php` defaults | `seo_title`, `meta_description`, `robots`, `og_type`, `twitter_card` set; `og_image` nullable | [`config/seo.php`](config/seo.php#L3-L12) |
| Factory defaults safe | `ProductFactory`, `PageFactory`, `BlogArticleFactory` default `published => false` | [`database/factories/ProductFactory.php`](database/factories/ProductFactory.php#L29) |
| Seeder integrity | No prices, no warranties, no user accounts; blog/FAQs/pages seeded as draft | [`database/seeders/ContentSeeder.php`](database/seeders/ContentSeeder.php#L36-L41) |
| Phase 4 boundary clean | No `Lead`, `Analytics`, `PushNotification`, `Vapid`, `WebPush`, or `WhatsApp` classes in `app/` | Regex: 0 results |
| SQLite test DB | `phpunit.xml` forces `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` | [`phpunit.xml`](phpunit.xml#L28-L29) |
| `BrandController` dual-model handling | Separate method sets for `ProductBrand` and `PackageBrand`; model binding via route parameter type-hint; no cross-tenant confusion | [`app/Http/Controllers/Admin/BrandController.php`](app/Http/Controllers/Admin/BrandController.php#L15-L69) |

## Findings

### Critical

None identified.

### High

- **H1 — Slug generation TOCTOU race.**
  [`app/Models/HasSlugs.php`](app/Models/HasSlugs.php#L36-L49): `generateUniqueSlug` uses a `while (...->exists())` loop. Under concurrent creates, two requests can both determine `slug = 'foo'` is free, both assign it, and one hits the unique index with an unhandled `QueryException` (500) instead of a clean 409/422. **Risk:** intermittent 500s under concurrent admin saves. **Smallest remediation (not applied):** wrap the check-and-insert in `DB::transaction()` + `lockForUpdate()`, or catch the unique-constraint violation and retry with a suffixed slug.

- **H2 — No `canonical` default in `config/seo.php`.**
  [`app/Services/SeoMetadataResolver.php`](app/Services/SeoMetadataResolver.php#L45-L50): when no `SeoMetadata` row exists and no page-level `canonical` override is passed, `firstNonEmpty` returns `null`, and the Blade layout emits an empty `<link rel="canonical">` tag. [`config/seo.php`](config/seo.php#L3-L12) defines `og_image => null` but no `canonical` key. **Risk:** empty canonical violates AGENTS §12 "Do not produce duplicate or conflicting canonical tags". **Smallest remediation (not applied):** add `'canonical' => null` to `config/seo.php` defaults and have the Blade layout omit the tag when the resolved value is `null`.

- **H3 — `MediaAssetController::index()` N+1.**
  [`app/Http/Controllers/Admin/MediaAssetController.php`](app/Http/Controllers/Admin/MediaAssetController.php#L11-L15): `index()` paginates without eager-loading `creator` (a `belongsTo(User)` relation). Any view or JSON serialization that accesses `$asset->creator` triggers one query per asset. **Risk:** 25 extra queries per page at the default page size. **Smallest remediation (not applied):** add `->with('creator')` to the query, or exclude `creator` from the JSON payload.

### Medium

- **M1 — Author spoofing via `user_id` input on blog articles.**
  [`app/Http/Requests/Admin/StoreBlogArticleRequest.php`](app/Http/Requests/Admin/StoreBlogArticleRequest.php#L19): `user_id` is validated with `Rule::exists('users','id')` but is not constrained to the authenticated user. An admin can set `user_id` to any valid user ID, attributing the article to another author. **Risk:** audit-trail integrity; the `blog_articles.user_id` FK becomes unreliable for ownership. **Smallest remediation (not applied):** replace `user_id => ['required', 'integer', Rule::exists(...)]` with `'user_id' => ['required', 'integer', 'same:'.auth()->id()]` or simply force `user_id => auth()->id()` in the controller and remove it from the request rules.

- **M2 — `FaqFactory` defaults to `published => true`.**
  [`database/factories/FaqFactory.php`](database/factories/FaqFactory.php#L19): unlike every other content factory, the FAQ factory defaults to published. Tests that expect draft FAQs must explicitly set `'published' => false`; any test that forgets will silently operate on published FAQs. **Risk:** test false-positives on draft-filtering logic. **Smallest remediation (not applied):** change the factory default to `published => false` to match the rest of the schema; the migration default (`faqs.published => true` at [`database/migrations/2026_01_01_000001_create_content_tables.php`](database/migrations/2026_01_01_000001_create_content_tables.php#L237)) is the root cause.

- **M3 — `services.type` has no unique index.**
  [`database/migrations/2026_01_01_000001_create_content_tables.php`](database/migrations/2026_01_01_000001_create_content_tables.php#L168-L182): `services.type` is `string(50)` with a plain index, not `unique()`. The PRD defines three fixed service types (`installation`, `repair`, `product_only`). An admin can create duplicate rows for the same type, breaking the assumption of one row per type. **Risk:** duplicate service rows; public index shows redundant entries. **Smallest remediation (not applied):** add a new migration with `Schema::table('services', fn($t) => $t->unique('type'))`, or enforce uniqueness at the form-request level with `Rule::unique('services','type')` excluding the current row on update.

- **M4 — `BrandController` correctly handles both product and package brands.**
  [`app/Http/Controllers/Admin/BrandController.php`](app/Http/Controllers/Admin/BrandController.php#L15-L69): two disjoint method sets (`productBrands`/`storeProductBrand`/`updateProductBrand`/`destroyProductBrand` and `packageBrands`/`storePackageBrand`/`updatePackageBrand`/`destroyPackageBrand`) each type-hint the correct model. Route parameters `{product_brand}` and `{package_brand}` in [`routes/web.php`](routes/web.php#L144-L154) bind to the correct models via implicit model binding. **No cross-tenant confusion found.** Finding M4 from the prior audit is a **false positive** and is removed.

### Low

- **L1 — All four prior test-coverage gaps are now closed.**
  The items flagged as missing in the prior security-review subtask are all present in `tests/Feature/`:
  - **draft-404-public:** `PublicContentTest::test_public_product_detail_returns_404_for_draft` ([`tests/Feature/PublicContentTest.php`](tests/Feature/PublicContentTest.php#L42-L46))
  - **slug-collision:** `SlugGenerationTest::test_slug_unique_for_same_content_type` ([`tests/Feature/SlugGenerationTest.php`](tests/Feature/SlugGenerationTest.php#L26-L31))
  - **SEO-fallback-when-no-row:** `PublicContentTest::test_seo_metadata_resolver_falls_back_to_model_title_when_no_seo_record` ([`tests/Feature/PublicContentTest.php`](tests/Feature/PublicContentTest.php#L171-L183))
  - **403-for-non-admin:** `ContentManagementTest::test_non_admin_user_gets_403_on_admin_routes` ([`tests/Feature/ContentManagementTest.php`](tests/Feature/ContentManagementTest.php#L35-L47))
  No additional low-coverage items identified.

- **L2 — `phpunit.xml` hardcoded test `APP_KEY`.**
  [`phpunit.xml`](phpunit.xml#L21): `base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=` is the standard Laravel test-skeleton key. Test-only; not a secret. No remediation required.

- **L3 — `ContentSeeder` silently skips blog seeding when no user exists.**
  [`database/seeders/ContentSeeder.php`](database/seeders/ContentSeeder.php#L131-L134): `if (!$user) { return; }`. Operator may not realize blog categories/tags were not seeded. **Smallest remediation (not applied):** add `report()->warning('Blog seeding skipped: no user found');` before the early return.

- **L4 — `AUDIT_REPORT.md` at repo root is stale relative to Phase 3.**
  [`AUDIT_REPORT.md`](AUDIT_REPORT.md): pre-existing file; scope and currency unknown. **Smallest remediation (not applied):** cross-check against this report or archive it before Phase 4.

## Security & Data-Integrity Risks

| Risk | Severity | Status |
|------|----------|--------|
| Public exposure of draft/unpublished content | Low | **Not found.** All public controllers use `published()` scope. `Faq.published` migration default is `true` (M2) but the seeder explicitly sets `false`. |
| Admin authorization bypass | None | Every admin route behind `auth` + `can:admin`; every form request re-checks `can('admin')`. |
| Mass-assignment injection of `role` | None | `User` model uses `#[Fillable]` attribute; no admin form request targets the `users` table. `role` set only via `admin:create` CLI. |
| Author spoofing on blog articles | Medium | M1 — `user_id` accepted from input without authentication check. |
| Slug uniqueness violation under concurrency | High | H1 — TOCTOU gap in `HasSlugs`. |
| Empty/missing canonical in production | High | H2 — no `canonical` default in `config/seo.php`. |
| N+1 queries in admin media list | High | H3 — `MediaAssetController::index()` lacks `with('creator')`. |
| Duplicate service types | Medium | M3 — no unique index on `services.type`. |
| Test false-positives on FAQ draft state | Low | M2 — `FaqFactory` defaults to `published => true`. |
| Secrets / credentials in repo | None | `.env` gitignored; no VAPID keys, no MCP tokens, no DB credentials committed. |

## MariaDB Verification Requirements

The following are **static analysis only** and must be confirmed on a live MariaDB 10.x / 11.x instance. No migration was executed as part of this audit.

1. **Run migrations on a disposable MariaDB database:**
   ```bash
   DB_CONNECTION=mariadb DB_HOST=127.0.0.1 DB_PORT=3306 DB_DATABASE=wpcctv_test DB_USERNAME=root DB_PASSWORD='' php artisan migrate
   ```
   Confirm all 17 content tables + `users.role` are created without error.

2. **Verify index names do not exceed MariaDB's 64-character limit.**
   Generated names from the migration:
   - `product_brands_name_index`, `product_brands_slug_unique`
   - `products_brand_id_published_index`, `products_name_index`
   - `package_categories_slug_unique`
   - `package_category_package_package_category_id_package_id_unique`
   - `package_usage_category_package_package_usage_category_id_package_id_unique`
   - `blog_article_tag_blog_article_id_blog_tag_id_primary`
   - `faqs_context_type_context_id_published_index`

   All under 64 chars. **Verify on MariaDB** with `SHOW INDEX FROM <table>;` and confirm no truncation warnings.

3. **Confirm column types accepted.**
   `json` columns natively supported in MariaDB 10.2+. `decimal(12,2)`, `tinyInteger`, `longText`, `unsignedBigInteger` fine. No `->change()` or raw `ALTER TABLE` present, so no `doctrine/dbal` dependency required. No `enum` columns used.

4. **Confirm `constrained()` generates correct FK constraints.**
   `SHOW CREATE TABLE products;` should show `CONSTRAINT ... FOREIGN KEY (brand_id) REFERENCES product_brands(id) ON DELETE CASCADE`.

5. **Confirm `unique(['seoable_type', 'seoable_id'])` is enforced.**
   Attempt `INSERT INTO seo_metadata (seoable_type, seoable_id, ...) VALUES ('App\Models\Product', 1, ...)` twice — second insert must fail.

6. **Confirm `blog_article_tag` composite primary key.**
   `SHOW CREATE TABLE blog_article_tag;` should show `PRIMARY KEY (blog_article_id, blog_tag_id)`.

7. **Run the full PHPUnit suite against MariaDB** (not SQLite) to catch SQLite-specific behavior differences:
   ```bash
   DB_CONNECTION=mariadb DB_DATABASE=wpcctv_test php artisan test
   ```
   Watch for: `LIKE` case-sensitivity differences, `AUTO_INCREMENT` vs `rowid`, `json` column comparison semantics.

## Missing Acceptance Criteria

AGENTS §17 / §19 items not yet verified:

1. **MariaDB schema verification.** All schema claims above are static. **Status: `pending`** — requires `php artisan migrate` on MariaDB 10.x/11.x.
2. **Browser tests (AGENTS §17 Frontend: Turbo Drive, Frames, Streams).** Phase 3 is backend-only; browser tests out of scope but not yet run.
3. **SEO crawler validation.** AGENTS §12 requires independent social-crawler validation of Turbo-updated head. Not tested.
4. **Preline Theme Generator output or MCP integration.** Phase 2 blocker per `docs/ARCHITECTURE.md`. Phase 3 does not claim Preline.
5. **5-role authorization matrix.** Deferred to Phase 4 per `docs/ARCHITECTURE.md`.
6. **Media file upload endpoint.** `media_assets` tracks metadata only; no binary upload controller or storage disk configuration verified.
7. **Queue worker / scheduler configuration for MariaDB session/queue drivers.** Deployment concern per AGENTS §17.

## Recommended Remediation (ordered by priority)

1. **H1 — Fix `HasSlugs` race condition.** Wrap slug generation in `DB::transaction()` + `lockForUpdate()`, or catch the unique-constraint violation and retry with a suffixed slug. Add a concurrent-create integration test.
2. **H2 — Add `canonical` handling to `config/seo.php` and Blade layout.** Add `'canonical' => null` to defaults. Update the Blade layout to omit the `<link rel="canonical">` tag when the resolved value is `null`.
3. **H3 — Eager-load `creator` in `MediaAssetController::index()`.** Add `->with('creator')` to the paginated query.
4. **M1 — Constrain `user_id` to authenticated user in blog article requests.** Force `user_id = auth()->id()` in the controller and remove `user_id` from the request's fillable/validation surface.
5. **M3 — Add unique index on `services.type`.** New migration: `Schema::table('services', fn($t) => $t->unique('type'))`.
6. **M2 — Change `Faq.published` migration default to `false`.** New migration: `Schema::table('faqs', fn($t) => $t->boolean('published')->default(false)->change())` (requires `doctrine/dbal` on pre-MariaDB-10.4; on 10.4+ use `ALTER TABLE`). Also align `FaqFactory` to default `published => false`.
7. **L3 — Log a warning in `ContentSeeder` when no user is found.**
8. **L4 — Cross-check or archive `AUDIT_REPORT.md`.**

## Test Suite Results

**Command:** `php artisan test` (in-memory SQLite, `phpunit.xml` forces `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)

| Metric | Value |
|--------|-------|
| Total tests | 67 |
| Passed | 67 |
| Failed | 0 |
| Skipped | 0 |
| Assertion count | 207 |
| Exit code | 0 |
| Duration | 12.84s |
| PHP / PHPUnit | PHP 8.3+, PHPUnit 12.5.43 |

**No failures. No stack traces. No assertion errors.**

### Test distribution by file

| File | Tests |
|------|-------|
| `tests/Unit/ExampleTest.php` | 1 |
| `tests/Feature/ExampleTest.php` | 1 |
| `tests/Feature/AuthenticationTest.php` | 10 |
| `tests/Feature/ContentManagementTest.php` | 27 |
| `tests/Feature/FoundationTest.php` | 4 |
| `tests/Feature/ModelRelationshipTest.php` | 7 |
| `tests/Feature/PublicContentTest.php` | 12 |
| `tests/Feature/SlugGenerationTest.php` | 5 |

## Phase 4 Readiness

**Can Phase 4 begin?** **Yes, with the following prerequisites resolved first:**

1. **H1 (slug race condition)** — must be fixed before Phase 4 introduces concurrent admin write paths (lead creation, WhatsApp click tracking).
2. **H2 (missing canonical default)** — must be fixed before Phase 4 adds SEO metadata for lead/landing pages; otherwise new pages inherit the empty-canonical bug.
3. **H3 (MediaAsset N+1)** — should be fixed before Phase 4 adds media-to-lead associations.
4. **M1 (author spoofing)** — should be fixed before Phase 4 adds lead-ownership attribution; the same pattern of accepting a user-supplied `user_id` could recur.
5. **MariaDB schema verification** — the 17 content tables must be confirmed to migrate cleanly on the target MariaDB version before Phase 4 adds lead/analytics tables on top.

**Phase 4 must NOT begin until items 1–5 are closed.** Items 6–7 (M2, M3, L3, L4) are non-blocking for development but should be batched into the same remediation pass.

The Phase 4 boundary is clean: no `Lead`, `Analytics`, `PushNotification`, `Vapid`, `WebPush`, or `WhatsApp` tracking classes exist in `app/`. No Preline references exist in `resources/`. No admin UI Blade components beyond placeholder views. `routes/web.php` contains no Phase 4 routes.
