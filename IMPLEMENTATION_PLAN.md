# Implementation Plan

**Status:** Phase 5 (Public Frontend + Demo Seed Data) delivered on top of Phase 3 (content backend) and Phase 4 (business dashboard). Preline and MariaDB runtime verification remain open.

## Phase 3 Delivered

- 17 content tables (products, packages, services, blog, FAQs, pages, media, SEO metadata, brand/category taxonomies) in single MariaDB-compatible migration.
- 14 Eloquent models with relationships, JSON casts, published() scopes, and HasSlugs auto-generation trait.
- `users.role` string column (default `admin`); native `Gate::define('admin')` registered in `AppServiceProvider`.
- `SeoMetadataResolver` service with 4-tier fallback chain (explicit SEO → model attrs → caller defaults → global config).
- `config/seo.php` global SEO defaults.
- Admin JSON API: full CRUD for products, packages, services, blog, FAQs, SEO metadata, media assets, pages, and taxonomies.
- Form requests with validation; 13 content factories; `ContentSeeder` (draft placeholders only).
- Tests: `ContentManagementTest`, `PublicContentTest`, `SlugGenerationTest`, `ModelRelationshipTest`.

## Phase 4 Delivered

- WhatsApp first-party tracking: `GET /track/whatsapp` → record click → queue notification → redirect to wa.me. Server-controlled destination, truncation-hardened dimensions, visitor-token cookie, per-visitor rate limiter.
- Lead management: manual + click-conversion flows, 8 statuses, policy-scoped, audited status changes, activity timeline.
- Analytics: page-view recording (DNT/GPC-honoring), daily aggregation, report service with zero-denominator guards, admin UI + JSON APIs.
- Browser push: VAPID, service worker, subscription management, notification preferences, queue-based delivery with expiry handling.
- Activity logging with redaction; settings with strict whitelist; retention cleanup job.
- 5-role authorization matrix (super_admin, administrator, content_editor, lead_manager, analyst) with gate-aware admin UI.

## Phase 5 Delivered — Public Frontend + Demo Seed Data

- **Public pages (server-rendered Blade, Turbo navigation, Indonesian URLs per DESIGN.md):** home (9 sections), `/produk` + detail (brand/type filters), `/paket-cctv` + detail (camera/usage/category filters), `/layanan` + detail, `/blog` + detail (category/tag/search), `/faq`, `/kontak` (info + lead form), `/halaman/{slug}` static pages, custom 404.
- **WhatsApp CTAs everywhere** via the existing `whatsapp.track` endpoint with contextual prefilled messages; `App\Support\WhatsAppLink` centralizes URL building.
- **SEO:** centralized head (title/description/canonical/robots/OG/Twitter + `og:site_name`, `og:locale`), JSON-LD (LocalBusiness, BreadcrumbList, Product, Service, Article, FAQPage) via `App\Services\StructuredData` — no `Offer`/price structured data (no e-commerce), dynamic `/sitemap.xml` (published content + non-empty archives, cached 1h) and `/robots.txt` following APP_URL.
- **Public lead form:** validated Form Request, honeypot spam filter, 5/min IP rate limiter, writes a `leads` row (status `new`) + `identify` activity — reuses the existing lead pipeline, no admin behavior changed.
- **Brand/contact layer:** `App\Support\SiteInfo` (settings table → `config/site.php` fallback, per-request cache) with WhatsApp normalization; `config/site.php` brand defaults; `App\Support\Prose` escape-first plain-text renderer (closes W6 for this format).
- **Responsive + accessible:** mobile-first layout, hamburger drawer (Stimulus, Turbo-safe), skip link, aria-current, labelled forms, visible focus, min-h-11 targets; browser-verified no horizontal overflow at 390px and working mobile menu.
- **Seeders (all idempotent, verified twice + by test):** `SettingSeeder` (site_name "WP CCTV Indonesia", whatsapp_number "6287811556995", email, hours, areas), `UserSeeder` (demo author, no default credential), `ProductSeeder` (6 brands, 12 products with real model numbers/specs/reference prices), `PackageSeeder` (6 packages with equipment/scope/exclusions/process/pivots/prices), `ServiceSeeder` (3 services), `BlogSeeder` (4 categories, 6 tags, 7 published articles 386–435 words + 1 demo draft), `PageSeeder` (10 FAQs, 5 static pages with SEO).
- **Tests:** +29 (`PublicFrontendTest` 20, `SeederIdempotencyTest` 9): all pages 200, drafts 404, filters work, CTA carries the right number and context, contact form validates/stores/honeypot-rejects, sitemap excludes drafts, robots directives, 404 page, seeder idempotency/completeness/no-lorem/no-default-credential.
- **URL rename:** public routes now `/produk`, `/paket-cctv`, `/layanan`, `/halaman` per DESIGN.md §8–13 (was `/products`, `/packages`, `/services`, `/pages`); tests updated, no redirects added yet (see open items).

## Verification Evidence (Phase 5, 2026-11-16)

- `php artisan migrate:fresh --seed` (SQLite): 7 migrations + 7 seeders, exit 0.
- `php artisan db:seed` twice more: exit 0 both times, no duplicates (asserted by test).
- `php artisan route:list`: 61 routes, no duplicates/errors; all admin routes byte-identical to Phase 4.
- `php artisan test`: **383 passed (1474 assertions), exit 0** (baseline 354).
- `npm run build`: exit 0 (app-CE9VPR9c.css 74.31 kB, app-C-UAQP1W.js 142.14 kB).
- Manual: /, /produk, /paket-cctv, /layanan, /blog, /faq, /kontak, /halaman/tentang-kami, /sitemap.xml, /robots.txt → 200; /halaman/tidak-ada → 404; detail pages → 200; `track/whatsapp?context=home` → 302 `https://wa.me/6287811556995?text=…`.
- Browser (Playwright): homepage renders all sections with seeded data; mobile 390px no horizontal overflow; mobile menu opens/closes; console clean.

## Remaining Blockers / Open Items

1. **MariaDB runtime verification (Gate A) — STILL OPEN.** No reachable MariaDB on this host; `.env` ships empty DB credentials. All schema/seed/test verification remains SQLite-only. Deployment gate.
2. **Preline UI, Theme Generator, MCP — STILL OPEN** (Phase 2 blocker). Public and admin UIs are hand-rolled on Tailwind v4 with provisional DESIGN.md tokens; identical styling debt across ~26 views.
3. **301 redirects for renamed public URLs** (`/products` → `/produk`, etc.) — not yet added; add before any search-indexed deployment.
4. **Sitemap cache invalidation** not wired into admin save paths (1h TTL bounds staleness; `SitemapBuilder::forget()` ready).
5. **No imagery:** products/packages render a neutral SVG placeholder; `og:image` absent on seeded content (resolver emits no empty tags). `media_assets` remains metadata-only.
6. **Contact form does not dispatch push notifications** (push is scoped to WhatsApp clicks per PRD §8); leads appear in the admin list with an `identify` activity.
7. **`.env` on this machine is `APP_ENV=production` with empty DB credentials** — untouched; local runs need env overrides.
8. Carried from Phase 4 audit: W1 (legacy `admin` role), W2 (retention vs lead provenance), W3 (degraded-logging indicator), W4 (sw.js scope), M3 (redaction matching), M5 (lead reassignment), C1 (VAPID generation on host), W5 (`welcome.blade.php` leftover), H1/H2/M2/M3 Phase 3 schema items pending MariaDB.

## Boundary

No commerce, cart, checkout, payment, inventory, or order creation. Products informational; packages service offers; prices presented as survey-adjustable starting points. No e-commerce interactions. No new Composer/NPM dependencies in Phase 5.
