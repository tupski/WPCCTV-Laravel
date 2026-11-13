# AGENTS.md
## WP CCTV Indonesia — AI Development Instructions

This document defines mandatory engineering instructions for all AI coding agents working on WP CCTV Indonesia.

The PRD.md is the authoritative source for product requirements.

The DESIGN.md is the authoritative source for visual design and user experience.

This project is a Laravel 13 service-oriented website and business dashboard.

It is not an e-commerce platform.

---

# 1. Project Identity

- Project: WP CCTV Indonesia.
- Domain: https://wpcctv.id
- Business: CCTV installation and repair.
- Backend: Laravel 13.
- Frontend: Blade.
- Navigation: Hotwire Turbo.
- Interactions: Stimulus.
- CSS: Tailwind CSS v4.
- UI: Preline UI.
- Database: MariaDB.
- Primary conversion: WhatsApp.

Business principle:

Products are equipment references.

Packages are installation service offers.

WhatsApp is the primary conversion channel.

---

# 2. Mandatory Documentation

Before any implementation, read:

1. AGENTS.md.
2. PRD.md.
3. DESIGN.md.
4. Existing architecture and source files.
5. Relevant official framework documentation.
6. Relevant Preline documentation and Agent Skills.

Never implement from assumptions when the required information is already documented.

If documentation conflicts, report the conflict before proceeding.

Do not silently modify project requirements.

---

# 3. Technology Constraints

## Backend

- Laravel 13.x.
- PHP 8.3 or newer, within supported compatibility.
- MariaDB.
- Eloquent ORM.
- Blade.
- Laravel queues.
- Laravel scheduler where required.
- Native Laravel validation and authorization.

## Frontend

- Hotwire Turbo.
- Turbo Drive.
- Turbo Frames.
- Turbo Streams.
- Stimulus.
- Tailwind CSS v4.
- Preline UI.

## Mandatory Turbo Integration

Use the maintained Laravel integration:

`hotwired-laravel/turbo-laravel`

Version compatibility and dependency constraints must be checked before installation.

Use the current compatible stable release.

Do not use the abandoned `hotwired/turbo-laravel` package.

Turbo must be the primary navigation mechanism.

### Navigation Rules

- Turbo Drive enabled by default.
- Internal navigation must preserve Turbo behavior.
- Turbo Frames for partial page updates.
- Turbo Streams for server-driven updates.
- Avoid unnecessary custom AJAX.
- Avoid replacing navigation with client-side routing.

Use full navigation only when explicitly justified.

## Prohibited Alternatives

Do not introduce:
- Livewire.
- Inertia.
- React.
- Vue.
- Next.js.
- A separate SPA.
- An unnecessary frontend framework.

Do not combine multiple competing interaction frameworks.

Stimulus is permitted for focused client-side behavior.

---

# 4. Mandatory Preline Workflow

Preline UI is the required component and visual foundation.

The following are mandatory:

1. Preline UI.
2. Preline Theme Generator Agent Skill.
3. Preline MCP Agent Skill.
4. Preline MCP server integration.
5. Preline documentation lookup for interactive components.

## 4.1 Official Agent Skills

Repository:

`https://github.com/htmlstreamofficial/preline`

Installation command:

`npx skills add htmlstreamofficial/preline`

Required skills:

- `preline-theme-generator`
- `preline-mcp`

Use the project-level shared Agent Skills location where supported:

`.agents/skills/`

For coding agents requiring dedicated skill directories, use their documented project-level locations and keep them synchronized.

The actual installed skill files must be inspected.

Do not assume a skill is installed simply because it is mentioned in documentation.

## 4.2 Theme Generator

The Theme Generator must be used to create the WP CCTV Indonesia theme.

Brand starting colors:

- Navy: #102A43.
- Blue: #1D4ED8.
- Accent: #2EA3F2.
- Light surface: #F8FAFC.

Requirements:

- Generate a consistent theme.
- Inspect generated theme output.
- Verify Tailwind CSS v4 compatibility.
- Ensure public website and admin share the same design tokens.
- Avoid inconsistent theme overrides.
- Keep the generated theme maintainable.

Do not independently invent a conflicting color system.

## 4.3 Preline MCP

MCP endpoint:

`https://mcp.preline.co`

Transport:
- Streamable HTTP.

Authentication:
- Bearer token.

The MCP token must be supplied through secure environment configuration.

Never hardcode credentials.

Do not commit API tokens to Git.

MCP must be used to:
- Discover components.
- Discover page blocks.
- Retrieve complete block source.
- Retrieve component JavaScript documentation.
- Retrieve framework integration guidance.
- Verify component initialization requirements.

## 4.4 MCP Usage Workflow

For a component:
1. Discover the relevant component section.
2. Identify the exact component ID.
3. Retrieve the component.
4. Inspect its markup.
5. Inspect JavaScript requirements.
6. Integrate it into Blade.
7. Test with Turbo navigation.

For a page or layout:
1. Discover available Preline blocks.
2. Select an appropriate base layout.
3. Fetch the required block.
4. Adapt it to WP CCTV content and brand.
5. Remove irrelevant elements.
6. Validate responsive behavior.

For a dashboard:
- Start from a suitable Preline dashboard/application layout.
- Adapt the existing layout rather than inventing a new component system.
- Preserve consistent spacing, navigation, and theme tokens.

## 4.5 Preline Interaction Rules

Preline components must be initialized correctly.

Turbo navigation introduces lifecycle considerations.

Agents must:
- Follow Preline initialization documentation.
- Prevent duplicate event listeners.
- Handle Turbo navigation lifecycle correctly.
- Avoid stale DOM references.
- Ensure modals and dropdowns remain functional after navigation.
- Avoid repeated initialization that causes duplicated behavior.

Do not blindly paste markup without integrating its dependencies.

## 4.6 Preline License and Access

Before relying on Pro-only blocks, templates, or MCP access, verify the current license and subscription entitlement.

Do not assume every catalog item is free.

Prefer free components and blocks unless premium access is explicitly available and approved.

---

# 5. Architecture Principles

Use a maintainable Laravel modular monolith.

Recommended separation:

- Public website.
- Admin application.
- Content management.
- Catalog management.
- Blog management.
- Lead management.
- Analytics.
- Notifications.
- SEO.
- System management.

Avoid premature microservices.

Avoid unnecessary abstraction layers.

Separate:
- Business logic.
- HTTP controllers.
- Data access where useful.
- Presentation.
- Validation.
- Authorization.
- Background jobs.

Use service classes or actions when they improve clarity and reuse.

Do not create generic repositories for every Eloquent model without a clear reason.

---

# 6. Public Website Rules

Public pages must use server-rendered Blade.

Required:
- Homepage.
- Product catalog.
- Product detail.
- Package catalog.
- Package detail.
- Service pages.
- About.
- Contact.
- Blog.
- Blog categories.
- Blog tags.
- Article detail.

Every public page must:
- Be responsive.
- Be accessible.
- Be SEO-friendly.
- Have appropriate metadata.
- Support Open Graph.
- Preserve Turbo navigation.
- Have relevant WhatsApp CTAs where appropriate.

Never create e-commerce interactions.

---

# 7. Product and Package Rules

## Products

Products are equipment references.

Allowed:
- Specifications.
- Features.
- Images.
- Brand.
- Applications.
- Related packages.
- WhatsApp consultation.

Forbidden:
- Cart.
- Checkout.
- Buy Now.
- Quantity selection.
- Shopping inventory.
- Order creation.

## Packages

Packages represent installation services.

Allowed:
- Camera quantity.
- Included equipment.
- Installation scope.
- Application.
- Technical information.
- Exclusions.
- Related products.
- WhatsApp quotation inquiry.

Forbidden:
- Shopping cart.
- Checkout.
- Payment.
- Online purchase.

Never invent equipment inclusions, package prices, warranties, or installation conditions.

---

# 8. WhatsApp Tracking

Every first-party WhatsApp CTA must use a centralized tracking flow.

Required behavior:

1. Receive tracking request.
2. Validate destination configuration.
3. Record click event.
4. Queue notification job.
5. Redirect to WhatsApp.

The redirect must not wait for notification delivery.

Use safe redirect handling.

Never accept arbitrary external redirect destinations from user input.

Track only necessary information.

Do not collect personal data without a legitimate purpose.

Distinguish click events from identified leads.

Do not claim that a WhatsApp message has been sent or received.

---

# 9. Lead Management

Lead management is semi-automatic.

No mandatory pre-contact form.

A click event may be converted into an identified lead through admin action.

Admin can:
- Identify the customer.
- Add WhatsApp number.
- Set lead type.
- Update status.
- Add notes.
- Assign ownership.
- Schedule follow-up.
- View interaction history.

Lead status changes must be auditable.

Avoid duplicate lead records where reasonable.

Do not assume a browser session equals a unique customer.

---

# 10. Browser Notifications

Use Web Push API, Service Worker, and VAPID.

Requirements:

- Authenticated admin subscriptions.
- Explicit notification permission.
- Secure subscription storage.
- Subscription expiration handling.
- Multi-device support.
- Queue-based push delivery.
- Failure handling.
- Notification preferences.
- Notification click deep links.

Never request push permission from public website visitors.

Never put customer personal information in notification previews.

Do not block WhatsApp redirects.

Test actual browser delivery rather than only checking whether a queue job was dispatched.

Use Turbo Streams for live dashboard updates where useful.

---

# 11. Analytics

Define metric calculations before implementing charts.

Distinguish:
- Sessions.
- Page views.
- Unique estimated visitors.
- WhatsApp click events.
- Identified leads.
- Qualified leads.
- Won leads.

Do not treat clicks as leads automatically.

Do not treat leads as sales.

Avoid unbounded analytics queries.

Use appropriate indexes, aggregation, and caching.

Use asynchronous processing for expensive analytics tasks.

Analytics must respect privacy and configured retention policies.

---

# 12. SEO and Open Graph

SEO is mandatory.

All public content types must support appropriate metadata.

Required:
- SEO title.
- Meta description.
- Canonical.
- Robots.
- OG title.
- OG description.
- OG image.
- Twitter/X metadata.
- Structured data where appropriate.

Use a centralized metadata resolver.

Define clear fallback rules.

Ensure OG values can be edited through admin.

Generate absolute OG image URLs.

Do not produce duplicate or conflicting canonical tags.

Do not generate misleading structured data.

Use server-rendered HTML.

Ensure Turbo navigation updates the document head correctly and validate social crawler behavior independently of Turbo.

Do not rely on JavaScript-only SEO rendering.

---

# 13. Turbo and Preline Lifecycle

All interactive features must survive Turbo navigation.

Verify:
- Initial page load.
- Turbo Drive navigation.
- Back and forward navigation.
- Turbo Frame replacement.
- Turbo Stream updates.
- Modal opening and closing.
- Dropdown interactions.
- Form validation.
- File uploads.
- Notification interactions.

Prevent duplicate event listeners and memory leaks.

Use Stimulus controllers with appropriate lifecycle cleanup.

Use Turbo's documented lifecycle events.

Do not use arbitrary page-load assumptions.

---

# 14. Security

Mandatory:
- Server-side authorization.
- CSRF protection.
- Input validation.
- Output escaping.
- Safe redirects.
- File upload validation.
- Rate limiting.
- Secure authentication.
- Secure session configuration.
- Activity logging.
- Push subscription protection.
- Secret management.

Never expose:
- VAPID private keys.
- MCP tokens.
- Application secrets.
- Database credentials.

Do not store unnecessary customer data.

---

# 15. Responsive Design

Both public website and admin must work on:

- Desktop.
- Tablet.
- Mobile.

Test:
- Navigation.
- Catalog filters.
- Product galleries.
- Package details.
- Forms.
- Admin sidebar.
- Tables.
- Charts.
- Modals.
- Notifications.

No horizontal overflow.

Do not assume desktop layouts will automatically work on mobile.

---

# 16. Development Workflow

Use explicit phases and approval gates.

## Phase 0 — Audit

Inspect the actual environment.

Deliver:
- PHP and Composer versions.
- Node and package manager versions.
- Laravel environment.
- MariaDB version.
- Existing repository structure.
- Dependencies.
- Deployment constraints.
- Preline availability.
- Agent Skill availability.
- MCP connectivity.
- Risks.

No implementation.

## Phase 1 — Architecture

Define:
- Application structure.
- Data model.
- Content relationships.
- Routes.
- Admin authorization.
- Turbo strategy.
- Preline integration.
- SEO architecture.
- Analytics events.
- Lead lifecycle.
- Push notification architecture.
- Testing strategy.

Identify all unresolved decisions.

No production modifications.

## Phase 2 — Foundation

Implement:
- Laravel 13 foundation.
- MariaDB connection.
- Authentication.
- Authorization.
- Turbo integration.
- Tailwind v4.
- Preline UI.
- Preline Theme Generator output.
- Preline MCP integration.
- Shared layouts.
- Responsive application shells.

Verify each foundation before expanding.

## Phase 3 — Core Website and CMS

Implement:
- Content models.
- Product management.
- Package management.
- Service management.
- Blog.
- Media.
- SEO.
- Public pages.

## Phase 4 — Business Dashboard

Implement:
- WhatsApp tracking.
- Lead management.
- Analytics.
- Browser notifications.
- Activity logs.

## Phase 5 — Quality Assurance

Run:
- Automated tests.
- Functional tests.
- Browser tests.
- Mobile tests.
- SEO validation.
- Performance tests.
- Security tests.
- Regression tests.

## Phase 6 — Release Preparation

Provide:
- Implementation summary.
- Architecture overview.
- Test evidence.
- Known limitations.
- Deployment checklist.
- Rollback strategy.

Production deployment requires explicit approval.

---

# 17. Testing Requirements

Test actual behavior.

Minimum coverage:

### Backend
- Validation.
- Authorization.
- Content relationships.
- SEO resolution.
- WhatsApp click recording.
- Lead conversion.
- Analytics aggregation.
- Push subscription management.
- Notification jobs.

### Frontend
- Turbo Drive.
- Turbo Frames.
- Turbo Streams.
- Stimulus lifecycle.
- Preline initialization.
- Mobile navigation.
- Filters.
- Forms.
- Modals.

### SEO
- Title.
- Description.
- Canonical.
- OG metadata.
- Twitter/X metadata.
- Sitemap.
- Robots.
- Structured data.

### Notifications
- Permission denied.
- Permission granted.
- Subscription creation.
- Subscription expiration.
- Push delivery.
- Notification click.
- Multiple devices.
- Unsubscribed devices.

---

# 18. AI Agent Operating Rules

Agents must:

- Read documentation first.
- Inspect existing implementation.
- Use official documentation.
- Use Preline MCP when implementing Preline UI.
- Use Theme Generator for the brand theme.
- Follow phase boundaries.
- Explain assumptions.
- Produce test evidence.
- Preserve existing functionality.
- Report blockers.
- Request approval before irreversible operations.

Agents must not:

- Skip architecture review.
- Invent business requirements.
- Introduce unapproved frameworks.
- Add e-commerce functionality.
- Ignore Turbo requirements.
- Bypass Preline workflow.
- Hardcode secrets.
- Claim unverified test results.
- Publish content without approval.
- Deploy without approval.

---

# 19. Definition of Done

A feature is complete only when:

1. It satisfies PRD.md.
2. It follows DESIGN.md.
3. It uses the approved architecture.
4. Turbo navigation works.
5. Preline behavior works.
6. Mobile behavior is verified.
7. Authorization is tested.
8. SEO requirements are satisfied where applicable.
9. Automated tests pass.
10. Documentation is updated.
11. Known limitations are disclosed.
12. No unauthorized production changes occurred.

**Primary engineering objective: Build a maintainable, responsive, SEO-first Laravel application that turns customer interest into measurable WhatsApp inquiries.**
