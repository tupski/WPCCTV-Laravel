# Modern Web Guidance Audit — WP CCTV Indonesia

Inspected 2026-11-13. Factual record. No code changes in this document's production task; the changes recorded below were made and verified in the preceding remediation task.

## 1. Guidance repo + version inspected

- Source: https://github.com/GoogleChrome/modern-web-guidance-src
- CLI: `modern-web-guidance@1.0.0` via `npx`
- Date inspected: 2026-11-13

## 2. CLI commands executed

Verbatim from prior runs:

- `npx modern-web-guidance search "semantic html accessible navigation"`
- `npx modern-web-guidance search "keyboard focus management"`
- `npx modern-web-guidance search "form validation error handling"`
- `npx modern-web-guidance search "image loading performance"`
- `npx modern-web-guidance search "responsive layout css"`
- `npx modern-web-guidance search "core web vitals inp"`
- `npx modern-web-guidance search "html sanitization"`
- `npx modern-web-guidance legacy-scan . --json` → `totalFindings: 0` across 33 guides / 35 rules
- `npx modern-web-guidance retrieve "html"`
- `npx modern-web-guidance retrieve "navbar"`
- `npx modern-web-guidance retrieve "sanitize-untrusted-html"`

## 3. Legacy scan findings

`legacy-scan . --json` returned `totalFindings: 0`. No deprecated HTML/CSS/JS patterns found. Covered already under the broader audit in [PHASE_3_CLOSURE_AUDIT.md](../PHASE_3_CLOSURE_AUDIT.md); no separate remediation track.

## 4. Relevant guide IDs + recommendations

| Guide ID | Recommendation relevant to this project |
|---|---|
| `html` | Semantic elements for document structure; single canonical resolution point in `<head>`. |
| `navbar` | Accessible navigation landmarks, skip links, keyboard-operable menu toggles. |
| `accessibility` | Keyboard focus management, visible focus states, contrast. |
| `forms` | Server-side validation with client feedback; no bare success/failure handling. |
| `sanitize-untrusted-html` | Sanitize any HTML rendered from untrusted input before output. |
| `optimize-image-priority` | Explicit `width`/`height`, `fetchpriority="high"` only for LCP image, `loading="lazy"` for below-fold images. |
| `performance` | INP: avoid long tasks on interaction; keep JS budget small; defer non-critical work. |

## 5. Findings mapped to project files

| Finding | File | Status |
|---|---|---|
| W1 SEO head unwired | `resources/views/layouts/document.blade.php` | FIXED |
| W2 config duplication | `config/seo.php` | FIXED |
| W8 twitter:card missing | `resources/views/layouts/document.blade.php` | FIXED |
| H1 slug race | `app/Models/HasSlugs.php` | FIXED |
| H3 media N+1 | `app/Http/Controllers/Admin/MediaAssetController.php` | FIXED |
| M1 author spoofing | `app/Http/Controllers/Admin/BlogController.php` | FIXED |
| W3 image attrs | n/a | N/A — no images rendered yet |
| W4 a11y landmarks | `resources/views/layouts/document.blade.php` | positive — semantic head/body structure, escaped output |
| W5 leftover scaffold view | `resources/views/welcome.blade.php` | P3 open |
| W6 HTML sanitization | n/a | P1-future open — needed when admin-authored HTML renders publicly |
| NEW-2 pivot index names | `database/migrations/2026_01_01_000001_create_content_tables.php` | open — needs live MariaDB |
| M3 `services.type` plain index | `database/migrations/2026_01_01_000001_create_content_tables.php` | P2 open |
| NEW-1 redundant morph index | `database/migrations/2026_01_01_000001_create_content_tables.php` | P2 open |
| M2 `faqs.published` default | `database/migrations/2026_01_01_000001_create_content_tables.php` | P2 open |

## 6. Implemented changes

All verified 2026-11-13 against working tree; full evidence in [PHASE_3_CLOSURE_AUDIT.md](../PHASE_3_CLOSURE_AUDIT.md).

- **H1** — `app/Models/HasSlugs.php:46-57`: `save()` override catches `UniqueConstraintViolationException`; `app/Models/HasSlugs.php:64-89`: retry loop with new slug candidates, budget exhaustion rethrows original exception; `app/Models/HasSlugs.php:59-62`: non-slug violations rethrown. Public trait API (`getSlugSource`, `bootHasSlugs`, `generateUniqueSlug`) unchanged.
- **H2** — `config/seo.php:8` has `canonical` key; `resources/views/layouts/document.blade.php:4-24` consumes `$seoData` with fallback chain (resolver `$seoData` → per-page `yieldContent` → `config('seo.defaults')` → `url()->current()`), emits og/twitter/robots/canonical at lines 28-44, no hardcoded duplication. All public controllers pass `$seoData` (grep-verified across `app/Http/Controllers/Public/`); per-page `@section('title')` / `@section('meta_description')` yields intact in all 12 public views.
- **H3** — `app/Http/Controllers/Admin/MediaAssetController.php:13`: `MediaAsset::with('creator')->latest()->paginate(25)`.
- **M1** — `app/Http/Requests/Admin/StoreBlogArticleRequest.php:16-32` and `app/Http/Requests/Admin/UpdateBlogArticleRequest.php:16-34`: no `user_id` rule. `app/Http/Controllers/Admin/BlogController.php:24-25` (store) forces `auth()->id()`; `app/Http/Controllers/Admin/BlogController.php:42` (update) strips `user_id` via `safe()->except()`. Grep across `app/`: no other admin controller accepts `user_id` from input.

## 7. Rejected recommendations + reasons

| Recommendation | Reason rejected |
|---|---|
| HTML sanitizer library | No sanitizer needed now: all Blade output uses `{{ }}` escaping; no admin-authored rich HTML rendered publicly yet. Re-evaluate when W6 becomes live. |
| New JS dependencies (e.g. for a11y polyfills) | No baseline-gap requiring polyfill; current JS is Turbo + Stimulus only. |
| Preline UI integration | Prohibited this phase per `docs/ARCHITECTURE.md` Phase 3 boundary; Preline tooling unavailable. |
| JSON-LD structured data | No evidence-backed business data yet (hours, addresses, ratings unconfirmed); generating it would violate AGENTS §12 "do not generate misleading structured data". |
| `fetchpriority` / `loading="lazy"` attributes | No images rendered in any template yet; nothing to attribute. |

## 8. Browser compatibility + fallback decisions

- Only baseline-widely-available features used: standard HTML5 head elements, `meta` tags, `link rel="canonical"`, CSS via Tailwind v4 build.
- No new Web APIs adopted requiring fallback.
- OG/Twitter meta tags are crawler-facing; no browser feature detection involved.
- No JavaScript added in this audit; Turbo/Stimulus behavior unchanged.

## 9. Tests executed + results

Re-run 2026-11-13 from clean working tree.

**Command:** `php artisan test`

Verbatim summary:

```
Tests:    74 passed (230 assertions)
Duration: 11.77s
```

Exit code 0. All suites passed, including:

- `tests/Feature/SlugGenerationTest.php`: `duplicate slug violation is retried not 500`, `two concurrent same title records get distinct slugs` (H1)
- `tests/Feature/SeoHeadTest.php`: `show page renders seo record values in head`, `show page without seo record uses current url canonical` (H2)
- `tests/Feature/ContentManagementTest.php`: `media index eager loads creator` (H3), `blog article authorship is server side`, `blog article update ignores user id` (M1)

**Command:** `npm run build`

Verbatim output:

```
> build
> vite build

vite v8.3.6 building client environment for production...
transforming...
✓ 6 modules transformed.
rendering chunks...
public/build/manifest.json              0.33 kB │ gzip:  0.16 kB
public/build/assets/app-C43Kne6y.css   61.76 kB │ gzip: 12.91 kB
public/build/assets/app-BNHQitJV.js   137.62 kB │ gzip: 35.69 kB

✓ built in 315ms
```

## 10. Remaining risks + unresolved

1. **Live MariaDB migration never run.** All schema is SQLite in-memory under `phpunit.xml`. NEW-2 (pivot index names), M3 (`services.type` index), NEW-1 (redundant morph index) unverifiable until `php artisan migrate` runs on target MariaDB 10.x/11.x.
2. **Pint full-repo style debt.** 45 pre-existing style issues reported in prior audit; untouched this pass.
3. **HTML sanitization deferred.** W6 stays open until public rendering of admin-authored HTML exists; at that point a sanitizer library becomes mandatory before render.
4. **Preline tooling unavailable.** Preline Theme Generator and MCP not installed; Phase 4 dashboard work per AGENTS §4 cannot start until they are provisioned.
5. **`resources/views/welcome.blade.php` leftover.** Default Laravel scaffold view still present; P3 cleanup.
6. **Phase 4 modules absent.** Grep of `routes/web.php` and `app/` for `Lead|Analytics|Push|VAPID|WebPush|WhatsApp` returned 0 matches. Boundary intact.
