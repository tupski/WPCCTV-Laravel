# Phase 4 Closure Audit

## Executive Verdict

**FAIL — BLOCKERS PRESENT**

Phase 4 (Business Dashboard) passes its own test suite (254 passed, 853 assertions, exit 0) but the suite does not assert the properties the phase was supposed to guarantee. Adversarial probing surfaced **four Critical defects**, two of which are latent MySQL-only failures (this audit's MariaDB gate was never cleared), one of which silently destroys a PRD-mandated capability, and one of which corrupts a stored analytics dimension. None of the four are covered by an existing test; all four are reachable in production.

The most consequential finding is **C1**: `vapid:generate` cannot produce a keypair. Every call to `openssl_pkey_new()` with this codebase's configuration returns `false` on the audited host, and the bundled minishlink helper fails identically. With no VAPID keys, `SendWhatsAppNotificationJob::webPush()` returns `null` and the HTTP path returns early — so "browser notifications are mandatory for WhatsApp click events" (PRD §8, L517) is quietly unimplemented, and the failure mode is a `Log::warning` nobody reads.

Second is **C2**: `visitor_token` is stored as a 312-character encrypted cookie blob into a `varchar(255)` column. SQLite accepted it (SQLite does not enforce `VARCHAR` length), which is precisely why the 254-test suite is green. MySQL/MariaDB in strict mode rejects it with `SQLSTATE 22001 Data too long`, and the write happens *before* job dispatch and *before* the redirect is returned — so on the real target database **every WhatsApp CTA click 500s and no visitor reaches WhatsApp.**

Third is **C3**: entity URLs are built as `config('app.url').ltrim($path, '/')`, which strips the leading slash and concatenates, producing `http://hostproducts/slug`. Every stored `page_url` for product/package/service/article/page clicks is malformed, every prefilled WhatsApp message carries the malformed link, and `context_id`-based reporting is built on a corrupted dimension.

Fourth is **C4**: the rate limiter keys on `$request->cookie('wpc_visit')`, which is `null` on a first-time visitor's click (the cookie is only set on the response). All cookie-less visitors therefore collapse into a single bucket — measured: requests 11 and 12 in a burst returned `429` regardless of distinct source IPs. A visitor without a cookie is rate-limited against every other first-time visitor on the site.

Below Critical, three High findings: push subscriptions that silently never deliver unless the admin happened to open a preferences screen (H1), a missing `notification_preferences` row making the mandatory feature a no-op (same root), a `referrer` written untruncated into a `varchar(255)` (H2, second MySQL-only 500 on the click path), and a data migration in the business-tables migration that rewrites `users.role` (H3, mixed DDL/DML, no down()).

The dashboard itself — the UI, the gate matrix, the aggregation semantics, the redaction, the settings whitelist — is genuinely well built and survived most probes. Verdict is FAIL on the four Critical items, not on craftsmanship.

## Verified Implementation

| Area | Verified | Evidence |
|------|----------|----------|
| WhatsApp redirect destination is server-controlled | `'https://wa.me/'.$phone.'?text='.rawurlencode($message)`; `$phone` from `Setting::get('whatsapp_number')` only, validated `regex:/^[0-9]{8,15}$/` | [`app/Services/WhatsAppTrackingService.php`](app/Services/WhatsAppTrackingService.php#L127-L134), [`app/Http/Requests/Admin/UpdateSettingsRequest.php`](app/Http/Requests/Admin/UpdateSettingsRequest.php#L46) |
| Open-redirect probe | `?phone=evil.com&number=evil.com&to=https://evil.com` → `Location: https://wa.me/628123456789?...` | Probe P11 |
| Redirect does not wait on the queue | `::dispatch()` (not `dispatchSync`), `QUEUE_CONNECTION=database` | [`WhatsAppTrackingService.php`](app/Services/WhatsAppTrackingService.php#L138); `php artisan tinker` → `{"queue":"database"}` |
| Message URL-encoded exactly once | `rawurlencode` applied once at redirect; `buildMessage` returns raw text | [`WhatsAppTrackingService.php`](app/Services/WhatsAppTrackingService.php#L134); Probe P2: `str_contains($loc,'%2520')` false, no `&` |
| No auto-send / no WhatsApp send API | No `wa.me` API call, no Graph API, no send endpoint anywhere in `app/`, `public/sw.js`, `resources/js` | Probe: `findstr /i "wa.me api.whatsapp graph.facebook"` → no send-side match |
| Lead statuses match PRD §7.4 exactly | 8 constants (`new, contacted, qualified, quotation, negotiation, won, lost, archived`) = PRD L484–492 (the brief's "seven" is wrong; PRD lists eight) | [`app/Models/Lead.php`](app/Models/Lead.php#L14-L27) vs [`docs/PRD.md`](docs/PRD.md#L484-L492) |
| Status change writes old **and** new value | `LeadActivity(action=status_change, field='status', old_value, new_value)` **plus** `ActivityLog` context `{old_value,new_value}` | [`app/Services/LeadConversionService.php`](app/Services/LeadConversionService.php#L95-L106); Probe P10 |
| No automatic click→lead conversion | `LeadConversionService::convert()` only reachable from admin controllers (`LeadViewController:211`, `LeadController:184`); `Lead::create` only in `LeadController:96` (admin, `status` forced to `new`) and inside `convert()` | `search_files` over `app/` |
| `leads_qualified` / `leads_won` derive from `lead_activities` | `statusTransitionsOn()` counts `ACTION_STATUS_CHANGE` rows with `new_value = $status` on that date — **not** current lead status | [`app/Services/AnalyticsAggregationService.php`](app/Services/AnalyticsAggregationService.php#L108-L113) |
| qualified→won still counts as qualified on the earlier day | Probe P5Q: yesterday `qualified=1, won=0`; today `qualified=0, won=1` — exactly as specified | Probe PQ output |
| Aggregation idempotent + bounded | `whereDate(...)->first() ?? new` + `fill()` + save; every metric a single indexed aggregate; `topContextId` is one `GROUP BY ... LIMIT 1` | [`AnalyticsAggregationService.php`](app/Services/AnalyticsAggregationService.php#L53-L71), [#L120-L133) |
| Ratios guard zero denominators | `$denominator === null \|\| $denominator === 0 ? null : ...`; `Format::percent(null)` → `—` | [`app/Services/AnalyticsReportService.php`](app/Services/AnalyticsReportService.php#L139-L141), [`app/Support/Format.php`](app/Support/Format.php#L42-L49); Probe P6: all ratios `null`, no `INF`/`NAN` |
| Push is admin-only | Route group inside `can:admin`; request `authorize()` → `can('admin')`; `user_id` server-forced | [`routes/web.php`](routes/web.php#L170-L174), [`StorePushSubscriptionRequest.php`](app/Http/Requests/Admin/StorePushSubscriptionRequest.php#L9-L13) |
| Role × push-route matrix | super_admin 201, administrator 201, content_editor 403, lead_manager 403, analyst 403, guest 403 | Probe P5 |
| Push payload carries no PII | Exactly `{title, body, url, event_id}`; `url` is a static `/admin/leads`, `body` is `page_title` only; no name, phone, IP, or query string | [`app/Jobs/SendWhatsAppNotificationJob.php`](app/Jobs/SendWhatsAppNotificationJob.php#L108-L116); Probe PR prints payload |
| 410/404 marks subscription inactive | `$report->isSubscriptionExpired()` → `active = false` | [`SendWhatsAppNotificationJob.php`](app/Jobs/SendWhatsAppNotificationJob.php#L140-L143) |
| Missing VAPID key no-ops instead of throwing | `vapid()` returns `null` → `webPush()` logs warning and returns `null` → `handle()` returns | [`SendWhatsAppNotificationJob.php`](app/Jobs/SendWhatsAppNotificationJob.php#L43-L75) |
| Service worker dedupe + deep link | `isDuplicate(eventId)` on a 60s window, Cache-API store; `notificationclick` opens stored URL | [`public/sw.js`](public/sw.js#L39-L68), [#L94-L120) |
| Activity log redaction covers the six patterns, incl. nested + mixed case | Recursive `redactArray`; `PASSWORD`, `Api_Key`, nested `Auth`, nested `P256DH`, `PASSWORD_CONFIRMATION` all `[redacted]` | [`app/Services/ActivityLogService.php`](app/Services/ActivityLogService.php#L25-L32), [#L95-L139); Probe P4 |
| Settings write path is a strict whitelist | `WRITABLE_KEYS = ['whatsapp_number','retention']`; `$request->safe()->only(...)`; `FORBIDDEN_KEYS` rejected with 422 | [`app/Http/Controllers/Admin/SettingController.php`](app/Http/Controllers/Admin/SettingController.php#L20-L31), [#L59-L69); Probe P3 |
| Private key cannot be written via any admin route | Direct `vapaid_private_key` → 422; attempt to smuggle it inside the whitelisted `retention` array was dropped by `normalizeRetention()` (stored `null`) | Probe P3 output |
| Retention defaults clearly provisional | `[PROVISIONAL]` comment on `DEFAULT_TIERS`; defaults 90/180/365 govern the purge | [`app/Jobs/RetentionCleanupJob.php`](app/Jobs/RetentionCleanupJob.php#L24-L34) |
| Retention tiers actually drive the cleanup | `array_merge(DEFAULT_TIERS, Setting::get('retention'))` then three `where('created_at','<',$cutoff)->delete()` | [`RetentionCleanupJob.php`](app/Jobs/RetentionCleanupJob.php#L38-L49) |
| Retention round-trip works on both write paths | JSON `PUT /admin/settings` → 200, stored `{30,60,120}`; UI `PUT /admin/settings-ui` → 302, stored `{45,75,150}` | Probe PC |
| Mass assignment blocked through form requests | Note POST with `assigned_to=999, status=won, converted_at=...` left the lead untouched; `lead_manager` reassigning another's lead → 403 | Probe PP |
| CSRF middleware present on `web` group | `Illuminate\Foundation\Http\Middleware\PreventRequestForgery` in the group | Probe PF output |
| Admin route gating (matrix) | See the full matrix below; every route returned the expected 200/403 | Probe P7 |
| Authentication / Phase 3 public surface intact | 254/254 green including `PublicContentTest`, `AuthenticationTest`, `ContentManagementTest`, `ModelRelationshipTest` | Suite run |

### Authorization matrix (executed, Probe P7)

| Route | super_admin | administrator | content_editor | lead_manager | analyst |
|---|---|---|---|---|---|
| `GET /admin/dashboard` | 200 | 200 | 403 | 403 | 403 |
| `GET /admin/leads-ui` | 200 | 200 | 403 | 200 | 403 |
| `GET /admin/analytics-ui` | 200 | 200 | 403 | 403 | 200 |
| `GET /admin/activity-logs-ui` | 200 | 200 | 403 | 200 | 200 |
| `GET /admin/settings-ui` | 200 | 200 | 403 | 403 | 403 |
| `GET /admin/push-settings` | 200 | 200 | 403 | 403 | 403 |
| `GET /admin/leads` (JSON) | 200 | 200 | 403 | 403 | 403 |
| `GET /admin/settings` (JSON) | 200 | 200 | 403 | 403 | 403 |

No unexpected 200 appears. `lead_manager` is correctly refused analytics; `analyst` is correctly refused leads and settings.

## Findings

### Critical

- **C1 — `vapid:generate` cannot create a keypair; browser push is silently dead.**
  [`app/Console/Commands/GenerateVapidKeysCommand.php`](app/Console/Commands/GenerateVapidKeysCommand.php#L18-L28) calls `openssl_pkey_new(['digest_alg'=>'sha256','curve'=>'prime256v1','private_key_bits'=>256])`. On the audited host this returns `false` for the shipped config, for the config without `private_key_bits`, and for `curve`-only; `curve_name`/`OPENSSL_KEYTYPE_EC` also returns `false`. `php artisan vapid:generate` → exit 1, `Gagal membuat kunci VAPID (openssl).` The library's own fallback fails identically: `Minishlink\WebPush\VAPID::createVapidKeys()` → `RuntimeException: Unable to create the key`. Therefore no operator can provision keys through the documented path. With keys absent, [`SendWhatsAppNotificationJob::vapid()`](app/Jobs/SendWhatsAppNotificationJob.php#L60-L75) returns `null`, `webPush()` returns `null`, and `handle()` returns at L81–L83. PRD §8 (L517) makes browser notifications **mandatory**; the phase therefore has no working notification path, and the only signal is `Log::warning('Push notification skipped: VAPID keys are not configured.')`. **Root cause is environmental** (OpenSSL EC generation unavailable as configured on this host) but the command has **no fallback and no diagnostic**, so the failure is indistinguishable from "not yet configured". **Smallest remediation (not applied):** use `VAPID::createVapidKeys()` when available, and on failure emit the `openssl_error_string()` chain so the operator can tell "lib not available" from "bad config". **What would verify a fix:** `php artisan vapid:generate` exit 0 plus a `Subscription`+`WebPush::sendOneNotification()` round-trip against a real push endpoint.

- **C2 — Encrypted visitor cookie is written into a `varchar(255)`; every click 500s on MySQL/MariaDB.**
  [`WhatsAppTrackingService::recordClick()`](app/Services/WhatsAppTrackingService.php#L163) stores `'visitor_token' => $request->cookie(self::VISITOR_COOKIE)`. That column is `$table->string('visitor_token')` → `varchar(255)` ([migration L41](database/migrations/2026_01_01_000002_create_business_tables.php#L41)). With `SESSION_ENCRYPT=true` (the `.env.example` default, [L30](.env.example#L30)) and **no** `EncryptCookies::except('wpc_visit')` in production — the `except()` call exists **only** in [`tests/TestCase.php`](tests/TestCase.php#L18) — the cookie reaches PHP as a Laravel-encrypted payload. Measured stored length: **312 characters** (Probe PL/PO), exceeding the column by 57. SQLite does not enforce `VARCHAR` length, so the 254-test suite never sees it; MySQL strict mode raises `SQLSTATE[22001] Data too long for column 'visitor_token'`. The insert at L152–164 runs **before** `dispatch()` (L138) and **before** the redirect is returned (L140), and `handle()` has no `try/catch`, so the visitor receives a 500 instead of reaching WhatsApp. **This is a production-blocking defect on the primary conversion path on the declared target database.** **Smallest remediation (not applied):** `EncryptCookies::except(WhatsAppTrackingService::VISITOR_COOKIE)` in `bootstrap/app.php`, and/or hash the token to 32 hex chars server-side before persisting. **What would verify:** the same probe against MariaDB with `SESSION_ENCRYPT=true`.

- **C3 — Entity URLs are missing their path separator; stored `page_url` and prefilled messages are malformed.**
  [`WhatsAppTrackingService::entityUrl()`](app/Services/WhatsAppTrackingService.php#L101) returns `config('app.url').ltrim($path, '/')`, where `$path` is `/products/{slug}` etc. (`ltrim` removes the slash, then the two are concatenated). Measured (Probe PJ, all five entity types):

  ```
  product: 'http://localhost:8002products/x'  expected 'http://localhost:8002/products/x'  -> MALFORMED
  package: 'http://localhost:8002packages/x'  ... MALFORMED
  article: 'http://localhost:8002blog/x'      ... MALFORMED
  page:    'http://localhost:8002pages/x'     ... MALFORMED
  service: 'http://localhost:8002services/x'  ... MALFORMED
  home:    'http://localhost:8002'            (correct, different code path)
  ```

  Three consequences: (a) every non-home `whatsapp_clicks.page_url` is corrupt, so source-page reporting is unusable; (b) `buildMessage()` embeds this URL into the prefilled WhatsApp text, so **the lead receives a dead link**; (c) `context_id` is still correct, so the corruption is confined to the URL column plus the outbound message. `home` is unaffected, which is why the existing tests (which exercise `home`) stay green. **Smallest remediation (not applied):** `rtrim(config('app.url'), '/').'/'.ltrim($path, '/')`. **What would verify:** a test asserting `entityUrl()` for a real published product series to `config('app.url').'/products/…'`.

- **C4 — Rate limiter collapses all cookie-less visitors into one bucket.**
  [`AppServiceProvider::boot()`](app/Providers/AppServiceProvider.php#L35-L40) keys the limiter on `$request->cookie(WhatsAppTrackingService::VISITOR_COOKIE)`. On a first click the cookie does not exist yet — it is minted on the **response** ([`applyVisitorCookie()`](app/Services/WhatsAppTrackingService.php#L183-L202)), and only *after* the click row is written. So `$key === null` for every first-time visitor, and the limit key becomes `whatsapp::home` for all of them. Measured (Probe PM): 12 sequential requests from 12 distinct `X-Forwarded-For` values and no cookie → `302 ×10, then 429, 429`. Control (Probe PN): 12 requests each carrying a **distinct** cookie → `302 ×12`, no 429. The limiter therefore works per-visitor *only after* the visitor already has the cookie; site-wide, the 11th and subsequent first-time clicks in any rolling minute are refused — and the refusal is a 429 instead of the WhatsApp redirect, i.e. **lost conversions under any burst of new traffic** (an ad campaign, a social post). **Smallest remediation (not applied):** fall back to `$request->ip()` when the cookie is absent, or key on `ip|context`. **What would verify:** the PM probe returning mostly 302 with distinct IPs.

### High

- **H1 — Push is delivered only to admins who have a `notification_preferences` row; a fresh deploy silently notifies nobody.**
  [`SendWhatsAppNotificationJob::handle()`](app/Jobs/SendWhatsAppNotificationJob.php#L85-L91) does `NotificationPreference::where('whatsapp_clicks_enabled', true)->pluck('user_id')` and passes that set to `User::admin()->whereIn('id', …)`. A row is created only by `NotificationPreference::forUser()`, called from [`PushSettingsViewController::index()`](app/Http/Controllers/Admin/PushSettingsViewController.php#L25) — i.e. **only when the admin opens the preferences screen**. Probe PR, with one active `PushSubscription` for an `administrator` and zero preference rows: `enabledUserIds = []`, `users receiving push = 0`, verdict `SILENT NO-OP despite active subscription`. `StorePushSubscriptionRequest` does not create a preference row either. The absence of a row means "enabled" by DB default (`notification_preferences.whatsapp_clicks_enabled` default `true`, [migration L115](database/migrations/2026_01_01_000002_create_business_tables.php#L115)) but the job's INNER-JOIN-by-`whereIn` turns "no row" into "no delivery". **Risk:** the mandatory feature appears wired, tests pass (they always seed the preference row first, e.g. [`NotificationJobTest:30`](tests/Feature/NotificationJobTest.php#L30)), and production never notifies. **Smallest remediation (not applied):** treat a missing row as enabled, e.g. `User::admin()->whereDoesntHave('notificationPreference', fn($q)=>$q->where('whatsapp_clicks_enabled',false))`, or create the row in `StorePushSubscriptionRequest`.

- **H2 — `referrer` is written untruncated into `varchar(255)`; a long `Referer` 500s the click.**
  [`WhatsAppTrackingService:158`](app/Services/WhatsAppTrackingService.php#L158) stores `$request->header('referer')` verbatim into `$table->string('referrer')` ([migration L36](database/migrations/2026_01_01_000002_create_business_tables.php#L36)). Contrast `AnalyticsRecorderService`, which *does* bound it — and reduces it to host only ([L113-L128](app/Services/AnalyticsRecorderService.php#L113-L128)). A `Referer` of 600 characters (legal; browsers send full URLs with query strings) overflows on MySQL strict → same `22001` → 500 on the conversion path, and also leaks the full external URL including its query string into storage, contradicting the privacy posture the recorder service observes. Probe P1 with a 600-char referer returned 302 on SQLite (no length enforcement). **Unverified on MySQL** — see Gate A.

- **H3 — The business-tables migration contains a data migration that rewrites `users.role`.**
  [`database/migrations/2026_01_01_000002_create_business_tables.php`](database/migrations/2026_01_01_000002_create_business_tables.php#L170-L179) runs `SELECT role FROM users GROUP BY role` and, for any row equal to `'admin'`, updates it to `'administrator'`. `down()` (L185–196) drops ten tables and does **not** reverse the role change. Three problems: (a) DDL and DML are mixed in one migration, so a partially applied migration leaves users un-migrated; (b) the "roles table" change is invisible in `add_role_to_users_table`, where a reader would look; (c) the transformation is not idempotent-safe in reverse — any environment that re-runs `down()`/`up()` silently keeps the rewritten value. It is *functionally* harmless today because `User::ADMIN_ROLES` includes the legacy `'admin'` string ([`app/Models/User.php`](app/Models/User.php#L41-L45)), so both values pass `can:admin`; the defect is migration hygiene, not access control.

### Medium

- **M1 — `RetentionCleanupJob` and `ExpirePushSubscriptionsJob` have no `$tries`/`$backoff`.**
  Only [`SendWhatsAppNotificationJob`](app/Jobs/SendWhatsAppNotificationJob.php#L26-L33) sets `$tries = 3` and `$backoff = [30,60,120]`. The purge and expiry jobs ([`RetentionCleanupJob`](app/Jobs/RetentionCleanupJob.php), [`ExpirePushSubscriptionsJob`](app/Jobs/ExpirePushSubscriptionsJob.php)) rely on queue defaults. A deadlock or lock-wait on the nightly purge retries once and then fails permanently with only a queue-failed-jobs row — retention quietly stops enforcing, which is a compliance-facing behaviour. Also note the purge issues three unbounded `DELETE`s in one transaction-less job; on a large table this is a long lock and an OOM risk on `activity_logs`.

- **M2 — Second-request `visitor_token` collision inflates the unique-visitor denominator.**
  Beyond the length bug (C2), the *semantics* are wrong independently. With the cookie encrypted in production, `$request->cookie('wpc_visit')` returns the **encoded** payload, so the stored token is the same 312-char blob for every click in that visitor's session — distinct clicks collapse to one token (Probe PL: `1 distinct of 2`). Conversely, a visitor who clears cookies, or any visitor before their first cookie, stores `NULL` — so `NULL` clicks are excluded from `distinct()->count('visitor_token')` derived from clicks. Net effect: the unique-visitor estimate is **simultaneously under-counting** (all of one visitor's clicks share one token, correct) and **dropping** all cookie-less clicks (incorrect). Quantified from the probes: in a two-click session the second click carries a 312-char token, the first carries `NULL`; `AnalyticsPageView` (which falls back to session id, [L46-L48](app/Services/AnalyticsRecorderService.php#L46-L55)) is unaffected, so `analytics_daily.unique_sessions` is sound while click-level unique counts are not. Fixing C2 (hash to 32 hex) also fixes this.

- **M3 — `isSensitiveKey()` over-redacts and under-redacts at the same time.**
  [`ActivityLogService::isSensitiveKey()`](app/Services/ActivityLogService.php#L141-L152) does a `str_contains` over `password, token, secret, key, p256dh, auth`. Over-redaction: any key containing `key` — `sort_key`, `cache_key`, `primary_key_label` — is blanked, and any containing `auth` — `author`, `author_name`, `authorized_by` — is blanked. The audit trail will lose the *author of a blog article* (a legitimate `author` field exists in Phase 3) the moment it is logged through this service. Under-redaction: `passwd`, `api-key` variants that normalise differently, and `PIN` are not matched (Probe P4: `passwd` came back as `NOT_COVERED?`). This is a correctness-of-audit issue, not a leak: the observed behaviour is fail-closed on the listed patterns.

- **M4 — `Format::number()` on a ratio path can render `INF` if a non-null infinite value ever reaches it.**
  [`Format::percent()`](app/Support/Format.php#L42-L49) guards `null` but passes any `float` to `number_format($ratio*100, ...)`. The report service correctly never produces non-finite values today (Probe P6: all `null`), so this is latent. It becomes live the moment any future caller computes a ratio without the `denominator === 0` guard, and `number_format(INF)` yields `0` (silently wrong) rather than an obvious failure.

- **M5 — `UpdateLeadRequest` permits `assigned_to` but the JSON `update` path re-checks nothing about the *policy* for reassignment.**
  [`LeadController::update()`](app/Http/Controllers/Admin/LeadController.php#L139-L146) accepts `assigned_to` and calls `$this->leads->assign(...)`. Authorization is `Gate::authorize('update', $lead)` — correct at the gate — but a `lead_manager` who can update a lead assigned to them can then reassign it **to anyone**, including away from themselves. `LeadPolicy::update()` only checks visibility ([L40-L43](app/Policies/LeadPolicy.php#L40-L43)); there is no rule that reassignment requires `manage-settings`. Probe PP confirmed the negative case (reassigning a lead *not* assigned to them → 403), but the positive case (reassigning one that *is* theirs, to a third party) is permitted and un-audited against a role rule.

### Low

- **L1 — `config/vapaid.php` is misspelled** (file name `vapaid.php`, key `vapaid_public_key`, while env vars are `VAPID_*`). Internal consistency is maintained (`Setting::get('vapaid_public_key')` everywhere), so nothing breaks — but it is a permanent papercut and guarantees future typos.
- **L2 — `notification_preferences` has `updated_at` but no `created_at`** ([migration L113-L118](database/migrations/2026_01_01_000002_create_business_tables.php#L113-L118)) with `$timestamps = false` on the model; the row's birth is unrecoverable.
- **L3 — `analytics_daily` has no index on `date` beyond the `unique`** — fine, but `AnalyticsReportService::forRange()` uses `whereDate('date', ...)` ([L59-L60](app/Services/AnalyticsReportService.php#L58-L62)), which wraps the column in a function and defeats the index on MySQL. A `where('date','>=',$from)` on a `date` column would use it. Bounded table, so cost is low.
- **L4 — `LeadPolicy::delete()` grants deletion to `manage-settings`/`manage-users`** ([L45-L48](app/Policies/LeadPolicy.php#L45-L48)) but no route exposes it; dead policy method.
- **L5 — `AnalyticsRecorderService::PAGE_TYPE_MAP` maps `faqs` → `faq`**, which is not in `WhatsAppClick::PAGE_TYPES`; the two vocabularies differ, so click-based and pageview-based reporting cannot be joined by type for FAQ.**

## Deviations judged

| Deviation | Judgement | Evidence |
|---|---|---|
| `visitor_token` NULL on a visitor's first click (cookie set on response only) | **NOT ACCEPTABLE — upgraded to Critical root cause (C2/C4/M2).** The "acceptable" framing understates it: combined with `SESSION_ENCRYPT=true` the *second* click stores a 312-char value into a 255-char column, so the deviation is not merely a NULL on click one, it is a 500 on click two. Impact quantified: 100% of entity-page clicks corrupted (C3), 100% of first-in-minute bursts beyond 10 rejected (C4), click-level unique counts unusable (M2). | Probes PL, PO, PM, PN |
| `EncryptCookies::except('wpc_visit')` applied in `tests/TestCase.php` only | **NOT ACCEPTABLE — it is the mechanism by which C2 hides.** Production behaviour with `SESSION_ENCRYPT=true` (the `.env.example` default) is: the cookie **does not round-trip as plaintext**; `$request->cookie()` yields the encrypted envelope, which is then persisted as a 312-char token. It neither round-trips correctly nor fails loudly — it silently breaks and passes tests. | [`.env.example` L30](.env.example#L30); [`tests/TestCase.php` L18](tests/TestCase.php#L18); Probe PA (set-cookie value begins `eyJpdiI6…`); Probe PO (312 chars) |
| VAPID keypair via raw OpenSSL instead of the library | **NOT ACCEPTABLE as implemented (C1); the *approach* is sound.** The code correctly builds `0x04‖X‖Y` with zero-padded 32-byte coordinates and base64url without padding — that is exactly what `minishlink/web-push` v11 consumes (`Subscription::__construct(string $endpoint, ?string $publicKey, ?string $authToken, …)`, [vendor L26-L30](vendor/minishlink/web-push/src/Subscription.php#L26-L30)). But `openssl_pkey_new()` returns `false` here, and so does the library's own `VAPID::createVapidKeys()`. Encode/decode round-trip could therefore **not be verified end-to-end** — the generator never produced a key to encode. Curve choice (`prime256v1` = P-256) and padding logic are correct by inspection; the runtime is the failure. | Probe PI (all four configs FALSE; artisan exit 1), Probe PK (`RuntimeException: Unable to create the key`) |
| `composer require minishlink/web-push` at runtime | **ACCEPTABLE.** `"minishlink/web-push": "^11.0"` in `require` — a caret range on 11.x, not `dev-master`, not floating to 12. Installed and autoloadable (`Subscription`, `WebPush::sendOneNotification` both resolve). | [`composer.json`](composer.json#L13); vendor API confirmed at [WebPush.php L146](vendor/minishlink/web-push/src/WebPush.php#L146) |

## Silent-failure probes

The brief asked specifically whether these services fail loudly or silently. Both swallow, and the swallow is *appropriate for infrastructure failure but dangerous for logic failure*:

- **`AnalyticsRecorderService`** ([L39-L61](app/Services/AnalyticsRecorderService.php#L39-L62)) wraps the whole `recordPageView` body in `try { } catch (Throwable) { Log::warning }`. Verified intended: dropping the `analytics_page_views` table left `GET /` at **200** (Probe P8) — a visitor still gets the page. **But the `try` also encloses `resolvePageType()`, `resolvePageUrl()`, `queryString()` and `DeviceDetector::detect()`, so a logic bug in type resolution is logged as a warning and the page still renders.** Recommendation: narrow the `try` to the `create()` call, so logic errors surface in tests.
- **`ActivityLogService`** ([L42-L59](app/Services/ActivityLogService.php#L42-L60)) returns `null` on any `Throwable`. Verified: dropping `activity_logs` made `log()` return `null` with no exception (Probe P9) — **audit records vanish silently, by design.** This is a defensible trade (an audit failure must not roll back a business action) but it means "audit trail is complete" is an article of faith, not an invariant. There is no compensating queue, no failure counter, and no admin-visible "audit degraded" state. Combined with **M5** (unaudited reassignment) and **M3** (over-redaction of `author`), the audit trail is the weakest guarantee in the phase despite the most elaborate code.
- **`SendWhatsAppNotificationJob`** — silent by construction in three distinct ways: missing keys (C1/H1), no preference row (H1), and an early `return` when `$users->isEmpty()`. Only transport errors throw (and are re-thrown deliberately at L137 so the queue retries), which is the one correct loud path.

## UI against the antislop constraints

| Constraint | Verdict | Evidence |
|---|---|---|
| No emoji | VERIFIED | Scanned all 14 admin Blade files with a `\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}` range: `files-with-emoji=0` |
| No lorem ipsum | VERIFIED | `findstr /i "lorem ipsum"` across `resources/views/admin/**` → 0 matches |
| No auto permission prompt on load | VERIFIED | No `Notification.requestPermission(` anywhere in admin views; [`PushSettingsViewController`](app/Http/Controllers/Admin/PushSettingsViewController.php#L16-L18) documents the button-gated flow |
| Zero denominator renders `—` | VERIFIED | [`Format::MISSING = '—'`](app/Support/Format.php#L19); `percent(null)` → `—`; template explains it at [analytics/index L93](resources/views/admin/analytics/index.blade.php#L93); Probe P6 produced `null` ratios, rendered as `—`. **One latent gap at M4.** |
| Status not conveyed by colour alone | VERIFIED | [`status-badge.blade.php` L13](resources/views/admin/partials/status-badge.blade.php#L13) always prints `{{ $label }}`; mapping uses `border-X text-X` with text always present; comment at L2–L3 states the rule |
| Labelled inputs | VERIFIED | `for=`/`id=` pairs on every filter and setting field, e.g. [leads/index L40,51,66](resources/views/admin/leads/index.blade.php#L40), [settings/edit L16,34,70](resources/views/admin/settings/edit.blade.php#L16) |
| Visible focus | VERIFIED | `:focus-visible { outline: 3px solid #2ea3f2; outline-offset: 3px; }` [app.css L24-L27](resources/css/app.css#L24-L27) |
| Tables scroll, not hide | VERIFIED | 8 `overflow-x-auto` wrappers across 6 admin views, each around a `min-w-[…]` table (e.g. dashboard L15/51/102, analytics L96) |
| Gate-aware nav | **PARTIAL / VIOLATED at the entry point** | Intra-page CTAs are gated — [`whatsapp-clicks L114,120`](resources/views/admin/analytics/whatsapp-clicks.blade.php#L114) uses `@can('manage-leads')`, [leads/index L98](resources/views/admin/leads/index.blade.php#L98) uses `auth()->user()->can('view-analytics')`. **But there is no global admin navigation partial at all** (grep for `admin.*-ui` route names finds only links inside individual views), so the dashboard is the de-facto nav. Its empty-state CTA links unconditionally to `admin.leads-ui.index` ([dashboard L47](resources/views/admin/dashboard/index.blade.php#L47)) and `admin.analytics-ui.whatsapp-clicks` (L98), and row links to `admin.leads-ui.show` (L67, L128). Those are safe **only because `/admin/dashboard` is itself `can:admin`-gated** and every `can:admin` role also holds `manage-leads`+`view-analytics`. The first time a role is added that can see the dashboard but not leads, these links become 403s. Latent, not observed. |

## Gate A status

- **MariaDB verification: STILL OPEN — and now materially more dangerous than at Phase 3 close.** `php -r` connection to `127.0.0.1:3306` → `SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it`. `php artisan migrate:fresh` was executed only against SQLite (all 6 migrations `DONE`, exit 0). `php artisan migrate:fresh --database=mysql` was **not run**; `config/database.php` defines both `mysql` (L47) and `mariadb` (L67) and `.env.example` ships `DB_CONNECTION=mariadb` (L21), so the target is the `mariadb` driver. Consequently **C2 and H2 are unverified on the target** — and the prediction is that both are hard failures, because SQLite does not enforce `VARCHAR(255)` while MySQL/MariaDB strict mode raises `22001`. What would verify: `DB_CONNECTION=mariadb php artisan migrate:fresh --force` on a reachable instance, then a single signed-in request to `/track/whatsapp?context=home` twice (C2) and once with a 600-char `Referer` (H2).
- **Preline decision: STILL OPEN.** Phase 4 was built **without** Preline — `package.json`/`package-lock.json` contain no Preline dependency (grep: 0 matches). The dashboard is hand-rolled on Tailwind v4 utilities. Resulting styling debt, precisely: (a) **no component layer** — every table, badge, form, and empty state is bespoke utility strings duplicated per view (`min-w-[36rem]`, `min-w-[48rem]`, `border-slate-200` repeated across 6 files), so a design change is an N-file edit; (b) **no design tokens consolidated** — `resources/css/app.css` still declares `--color-brand-*` as *"Provisional DESIGN.md tokens until Theme Generator output is verifiable"* ([L7](resources/css/app.css#L7)), so the admin and public surfaces share a token set that is explicitly unratified; (c) **no JS behaviour layer** — focus management, disclosure, and dismissible panels are absent, which is why the push widget is a plain form; (d) the `.npmrc`/`package.json` reveal only Turbo and Tailwind, so there is no vendored component audit trail. If Preline is later adopted, the debt converts from "cheap but duplicated" to "migration cost across 14 views".

## Regression check — Phase 3 surface

- **Public routes:** `PublicContentTest` and `PublicContentTest`-adjacent suites pass; `routes/web.php` still scopes every public controller inside the `RecordPageView` group (L50–78) and still calls `published()` via the controllers (verified indirectly — 254 green).
- **SEO head / slugs:** `SeoMetadataResolver` untouched; `HasSlugs` untouched (Phase 3 H1 TOCTOU race remains open and unfixed).
- **Auth:** `AuthenticationTest` green; `guest` group intact (L42–45); `redirect()->intended(route('admin.home'))` still at [AuthenticatedSessionController L45](app/Http/Controllers/Auth/AuthenticatedSessionController.php#L45).
- **Phase 4 changes that touch Phase 3 code paths:**
  1. [`AppServiceProvider::boot()`](app/Providers/AppServiceProvider.php#L42-L45) redefines the `admin` gate as `hasRole(...User::ADMIN_ROLES)` and `User::ADMIN_ROLES` deliberately includes the legacy `'admin'` string "so Phase 3 tests stay green" ([User.php L44](app/Models/User.php#L44)). This is a *test-greenness* accommodation baked into the authorization layer — it means an account with the stale role `'admin'` retains full admin access forever. Flagged as W1.
  2. The business-tables migration mutates `users` (H3) — a Phase 4 file altering a Phase 3 table without saying so in its name.
  3. `RecordPageView` was added to the **home route individually** (`Route::view('/', …)->middleware(RecordPageView::class)`, L40) *and* to the public content group (L50) — two application sites, so a future new public route gets analytics only if added to the group.
- **No Phase 3 test assertions were weakened or removed:** suite grew 74 → 254 tests / 230 → 853 assertions; every Phase 3 class still passes. `tests/TestCase.php` gained only the `EncryptCookies::except` line, which does not alter any Phase 3 assertion.

## What NOT to do

- **Do not "fix" C2 by widening `visitor_token` to `text`.** The column is not the bug; storing a transport-encrypted cookie value as a domain identifier is. Widening it makes the corrupt token *fit* and leaves M2 (token collision, unusable unique counts) in place.
- **Do not add `EncryptCookies::except('wpc_visit')` only in `bootstrap/app.php` and call it done.** That makes the cookie plaintext and C2's overflow disappear, but C4 (rate limiter keyed on a cookie that does not exist on click one) and M2 (`NULL` on first click) remain. Both need the cookie minted **before** the click is recorded — i.e. `ensureVisitorToken()` must run at the top of `handle()`, not in `applyVisitorCookie()` after it.
- **Do not repair C3 by `urldecode`/`rawurldecode`-ing the message.** The message is correctly encoded exactly once; only the host/path join is wrong. Touching the encoding reintroduces the double-encoding bug the phase correctly avoided (Probe P2 proves it is currently single-encoded).
- **Do not regenerate VAPID keys by shelling out to a different `openssl` binary.** Confirm `openssl_pkey_new()` can create EC keys on the deployment host first; if it cannot, the library helper will not either, and the failure must become an operator-visible error rather than `Log::warning`.
- **Do not resolve H1 by making `NotificationPreference` rows mandatory at deploy.** Backfilling rows would work, but it bakes in an "enabled by default" schema default that the operator never chose. Invert the predicate instead: absence of a row means enabled.
- **Do not treat the 254/254 green suite as evidence any of C1–C4 are fixed.** Every one of them is invisible to the existing suite by construction: SQLite does not enforce `VARCHAR`, the tests exercise `context=home` (which bypasses `entityUrl()`'s bug), the push tests always seed the preference row, and `TestCase.php` removes the cookie-encryption behaviour that production has. New tests must target MySQL + non-home contexts + a cookie-less second visitor + no seeded preference row.
- **Do not "improve" `ActivityLogService` redaction by adding patterns to `REDACTED_KEY_PATTERNS`.** The list is matching by `str_contains`; adding more broad substrings makes the over-redaction of `author`/`author_name` worse. Switch to an explicit deny-list of exact/`_`-delimited segments first.
- **Do not delete the data migration in H3 without first checking every environment's `users.role` distribution.** Both `'admin'` and `'administrator'` satisfy the gate today; removing the rewrite is safe only if no environment still holds `'admin'` and relies on the migration to normalise it.

## Open items carried forward from Phase 3

- **H1** (Phase 3) — `HasSlugs` TOCTOU race on concurrent creates: **STILL OPEN**, untouched by Phase 4.
- **H2** (Phase 3) — no `canonical` default in `config/seo.php`: **STILL OPEN**.
- **H3** (Phase 3) — `MediaAssetController::index()` N+1: **STILL OPEN** (no Phase 4 change to that file).
- **M1–M4** (Phase 3) — author spoofing, `Faq.published` default asymmetry, missing unique index on `services.type`, dead fallback in `UpdateBlogArticleRequest`: **ALL STILL OPEN**.
- **W5** (`resources/views/welcome.blade.php` leftover): **STILL OPEN**.
- **W6** (HTML sanitization before rendering admin-authored HTML): **STILL OPEN**, and now narrower — Phase 4 renders no admin-authored HTML on the public site, so the obligation has not yet crystallised.
- **Gate A** — MariaDB runtime verification: **STILL OPEN** (see above; now blocks C2/H2 sign-off too).
- **Preline** — **STILL OPEN**; Phase 4 shipped without it (see styling debt above).

## New open items introduced by Phase 4

- **W1** — Legacy `'admin'` role string accepted by `can:admin` indefinitely ([`User::ADMIN_ROLES`](app/Models/User.php#L41-L45)). The migration rewrites existing rows but the gate still accepts the old value, so any future insertion with role `'admin'` is a full admin. Decide and close.
- **W2** — `RetentionCleanupJob` prunes `whatsapp_clicks` at 180 days (`DEFAULT_TIERS`), but `lead_activities.whatsapp_click_id` has `nullOnDelete` ([migration L88](database/migrations/2026_01_01_000002_create_business_tables.php#L88)); after the purge, lead provenance is silently orphaned. Retention policy and audit requirement are in tension and nobody owns the decision. `[PROVISIONAL]` is asserted in the code comment but not in any runbook.
- **W3** — No admin-visible indication that analytics or audit logging is degraded. Both services swallow by design (see "Silent-failure probes"); an operator cannot distinguish "no traffic today" from "the recorder has been throwing for a week".
- **W4** — `public/sw.js` registers with scope `/admin` and requires no auth; it serves only push display and stores `{event_id, timestamp}` in the Cache API. It leaks nothing today (payload has no PII, C1 prevents any delivery), but it is an unauthenticated same-origin script with a persistent cache namespace `__push-dedupe` that any page could read. Low, but worth a one-line comment or a scope restriction when push is actually enabled.

## Suite result (re-run 2026-11-16)

```
Tests:    254 passed (853 assertions)
Duration: 26.53s
exit 0
```

Run twice: before the probe files were added (34.42s) and after they were deleted (26.53s), identical counts. The probe files (`AuditProbeTest`, `AuditProbeTwoTest`, `AuditProbeThreeTest`, `AuditProbeFourTest`, `AuditProbeFiveTest`, `AuditProbeSixTest`) and the temporary `database/audit_tmp.sqlite` were **deleted before finishing**; `php artisan test` confirms the tree is back to its exact pre-audit state. No application code, migration, view, controller, or existing test was modified.

Migrations, SQLite only:

```
0001_01_01_000000_create_users_table ................. 333.73ms DONE
0001_01_01_000001_create_cache_table .................. 25.49ms DONE
0001_01_01_000002_create_jobs_table ................... 85.79ms DONE
2026_01_01_000000_add_role_to_users_table .............. 7.24ms DONE
2026_01_01_000001_create_content_tables .............. 351.10ms DONE
2026_01_01_000002_create_business_tables ............. 382.73ms DONE
```

MariaDB: **UNVERIFIABLE** — `SQLSTATE[HY000] [2002] No connection could be made because the target machine actively refused it`.

## Test integrity

No existing Phase 4 test was weakened or removed. Reviewed: `NotificationJobTest` (validates the no-PII payload by key-name string match, and the missing-VAPID no-op), `ActivityLogTest` (redaction, `never throws when persistence fails`, status-change logging), `LeadManagementTest` (conversion idempotence, policy scoping), `AuthorizationMatrixTest`, `AnalyticsRecordingTest`, `AdminDashboardUiTest`, `AdminLeadUiTest`, `AdminSettingsUiTest`, `AnalyticsAggregationTest`, `AnalyticsApiTest`, `NotificationPreferencesTest`.

The tests are individually sound but **collectively blind to the four Critical findings**, by construction rather than by weakness:

1. `RefreshDatabase` forces SQLite (`phpunit.xml`), where `VARCHAR(255)` is unenforced — C2 and H2 cannot fail.
2. Every tracking test exercises `context=home`, whose URL path never calls `entityUrl()` — C3 cannot fail.
3. Every push test seeds `NotificationPreference::factory()` before asserting — H1 cannot fail.
4. `tests/TestCase.php` strips cookie encryption, and no test makes a second request in a fresh-cookie-jar burst — C4 cannot fail.

Any closure claim for Phase 4 must therefore rest on new tests that deliberately defeat each of those four safety nets, on the target database, not on the current green suite.
