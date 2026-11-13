# Decisions Required

**Project:** WP CCTV Indonesia  
**Purpose:** Record only decisions that remain genuinely unresolved after reading [`AGENTS.md`](AGENTS.md), [`docs/PRD.md`](docs/PRD.md), and [`docs/DESIGN.md`](docs/DESIGN.md).  
**Status:** Planning only; no decision is executed by this document.

## Decision summary

Project documents resolve product direction, frontend/backend technology, business model, WhatsApp conversion principle, no-e-commerce boundary, required public/admin scope, documented role suggestions, SEO requirements, push-notification constraints, responsive expectations, and phased workflow.

Remaining verified decision areas are operational/runtime target, initial admin bootstrap policy, exact analytics/privacy retention period, and Preline entitlement/access confirmation. These decisions are required because repository contains no implementation, dependency state, runtime evidence, deployment definition, or Preline integration.

## 1. Hosting and deployment target

### Issue

Select approved hosting/deployment target and operational service assumptions. No hosting, deployment, CI/CD, container, web-server, process-manager, queue-worker, scheduler, storage, backup, monitoring, or HTTPS files were present. The audit could not probe deployment environment.

### Why it matters

Target determines supported PHP/Laravel runtime, MariaDB topology, queue worker, scheduler, TLS, filesystem/media storage, VAPID delivery, environment secret injection, backups, monitoring, migration process, and rollback design. It blocks reliable Phase 0 closeout, dependency selection, architecture constraints, and Phase 6 release preparation.

### Alternatives

1. Managed PHP hosting with MariaDB, worker/scheduler support, HTTPS, persistent or object media storage, backups, and deploy hooks.
2. Managed virtual server with PHP-FPM, web server, MariaDB, queue worker, scheduler, HTTPS automation, backups, monitoring, and operator ownership.
3. Approved container platform with managed MariaDB and external worker/scheduler, storage, secrets, monitoring, and rollback services.

### Recommendation

Choose managed infrastructure that explicitly supports Laravel 13, PHP 8.3+, MariaDB, persistent queue worker, scheduler, HTTPS, secure secret injection, backups, media storage, and rollback. Record exact provider, regions, environments, operational owners, and monthly cost ceiling before Phase 1 architecture execution.

### Blocks implementation?

**Yes.** Foundation and release architecture cannot be validated without target constraints. Local development planning may proceed only as non-production architecture work.

## 2. Initial admin access and bootstrap policy

### Issue

Choose how first admin account and initial authorization state are created. [`docs/PRD.md`](docs/PRD.md) requires user management, roles, and permissions and suggests roles; it does not define initial account bootstrap, invitation, recovery, or production seed policy.

### Why it matters

Bootstrap policy affects authentication security, authorization tests, deployment runbook, secrets, account recovery, audit logging, and risk of shipping a default credential. No authentication implementation exists.

### Alternatives

1. One-time production bootstrap command executed by an operator with secret input, disabled after first successful setup.
2. Owner-invited first admin through a controlled setup flow with expiring invitation and mandatory password or passkey setup.
3. External identity provider or managed SSO for admin access, if hosting and business operations support it.

### Recommendation

Use one-time operator-controlled bootstrap with no default credentials, secure secret input, forced credential setup, audit logging, and immediate disablement after success. Add invitation/recovery policy during architecture review.

### Blocks implementation?

**Yes for production authentication and release.** Foundation authentication can be designed only after policy approval.

## 3. Analytics and personal-data retention period

### Issue

Select exact retention periods and deletion/anonymization rules for analytics events, anonymous identifiers, WhatsApp click events, identified leads, activity logs, push subscriptions, and notification history. [`AGENTS.md`](AGENTS.md) requires configured privacy-aware retention; [`docs/PRD.md`](docs/PRD.md) requires privacy-aware tracking and analytics, but no exact period was found in reviewed documents.

### Why it matters

Retention affects schema, indexes, aggregation, storage cost, privacy controls, deletion jobs, reporting continuity, auditability, and what data may appear in admin views or notifications. Click events must remain distinct from leads and confirmed customers.

### Alternatives

1. Short operational retention for anonymous analytics and click events, with longer retention only for identified lead records and required audit records.
2. One uniform retention period for all analytics and activity data, with documented legal and operational justification.
3. Business-defined retention tiers: raw events, aggregated metrics, identified leads, and security audit records each receive separate periods.

### Recommendation

Use tiered retention: shortest practical period for raw anonymous events and identifiers, longer approved period for identified leads and audit records, and indefinite retention only for aggregated metrics when raw-data deletion cannot reconstruct personal information. Confirm exact durations with business/legal owner before schema and scheduler design.

### Blocks implementation?

**Yes for production analytics and privacy controls.** Metric prototypes may use synthetic data only; no real retention policy should be assumed.

## 4. Preline license and access entitlement

### Issue

Confirm whether project may use only free Preline components/blocks or has approved Pro/template/MCP entitlement. [`AGENTS.md`](AGENTS.md) requires Preline UI, Theme Generator, MCP workflow, secure token handling, and manual entitlement verification before relying on Pro material. Audit found no project integration, project skills, MCP configuration, or verified entitlement.

### Why it matters

Entitlement determines usable layouts/blocks, implementation schedule, licensing compliance, MCP access, and whether public/admin shells can be based on selected references. Tailwind v4 compatibility and generated theme output also need verification.

### Alternatives

1. Use free Preline components and blocks only; obtain required MCP access through approved secure configuration.
2. Confirm active Pro entitlement and document account/license ownership before using Pro blocks or templates.
3. Select a different approved free component composition if entitlement or MCP access cannot be established.

### Recommendation

Default to free Preline components and blocks. Confirm secure MCP access and entitlement manually before selecting any Pro-only material. Never commit or print MCP tokens.

### Blocks implementation?

**Yes for Preline-dependent Foundation work.** No Pro decision is needed if implementation stays within verified free assets, but MCP and Theme Generator workflow still require access confirmation.

## Decisions explicitly resolved by project documents

No decision request is needed for these items:

- Laravel 13, PHP 8.3+ compatibility target, MariaDB, Eloquent, Blade, queues, and scheduler.
- Hotwire Turbo as primary navigation; Turbo Drive default; Frames and Streams where required.
- Stimulus for focused interaction; no Livewire, Inertia, React, Vue, Next.js, or separate SPA.
- Tailwind CSS v4 and Preline UI as frontend foundation.
- Maintained `hotwired-laravel/turbo-laravel`; abandoned `hotwired/turbo-laravel` prohibited.
- Service-first CCTV installation/repair business; products are equipment references; packages are installation service offers.
- WhatsApp as primary conversion; click event is not proof of message delivery, lead, unique visitor, or sale.
- No cart, checkout, payment, online product purchase, inventory, order fulfillment, WhatsApp Business API, or full CRM.
- Authenticated-admin-only browser push with explicit permission, VAPID, service worker, queue delivery, failure handling, and no public visitor permission prompt.
- Server-rendered SEO, centralized metadata, canonical, Open Graph, Twitter/X, sitemap, robots, and appropriate structured data.
- Responsive and accessible public/admin interfaces with mobile as first-class target.
- Phase order and explicit production approval requirement in [`AGENTS.md`](AGENTS.md).

## Current boundary

No user decision was executed. No application, dependency, environment, database, deployment, Git, or production state changed. Phase 1 architecture execution is explicitly outside this task. Decisions above must be recorded before their blocked implementation gates are opened.

Related artifacts: [`AUDIT_REPORT.md`](AUDIT_REPORT.md) and [`IMPLEMENTATION_PLAN.md`](IMPLEMENTATION_PLAN.md).
