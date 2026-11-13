# Product Requirements Document (PRD)
## WP CCTV Indonesia

**Project:** WP CCTV Indonesia  
**Domain:** https://wpcctv.id  
**Brand:** WP CCTV Indonesia  
**Tagline:** SMART SECURITY SOLUTIONS  
**Application:** Laravel 13  
**Frontend:** Blade + Hotwire Turbo + Stimulus  
**UI System:** Preline UI + Tailwind CSS v4  
**Database:** MariaDB  
**Primary Conversion:** WhatsApp  
**Document Status:** Approved Product Direction

---

# 1. Product Vision

WP CCTV Indonesia is a service-oriented CCTV company website and business management platform.

The platform combines:

1. Public company website.
2. CCTV equipment reference catalog.
3. CCTV installation package catalog.
4. Installation and repair service information.
5. SEO-oriented blog.
6. Content management system.
7. Semi-automatic lead management.
8. Business analytics dashboard.
9. WhatsApp interaction tracking.
10. Browser push notifications for WhatsApp click events.

The platform is not an e-commerce application.

WP CCTV Indonesia sells CCTV installation and repair services, not individual CCTV equipment through online shopping transactions.

## 1.1 Primary Business Objective

Generate qualified customer inquiries for CCTV installation, repair, and consultation through WhatsApp.

## 1.2 Secondary Objectives

- Establish a professional online presence.
- Improve organic search visibility.
- Explain CCTV technologies and equipment.
- Present installation solutions clearly.
- Capture measurable customer interest.
- Understand website traffic and conversion behavior.
- Improve lead follow-up and customer acquisition.

---

# 2. Core Business Rules

These rules are mandatory.

1. The business primarily sells services.
2. Products are informational equipment references.
3. Installation packages are service offerings.
4. WhatsApp is the primary customer communication channel.
5. Lead tracking is semi-automatic.
6. Website visitors are not required to complete a form before contacting WhatsApp.
7. WhatsApp click tracking does not imply that a message was delivered or received.
8. No shopping cart or checkout functionality.
9. No WooCommerce.
10. No individual equipment purchasing workflow.
11. All major content must be manageable through the admin panel.
12. Public pages must be SEO-friendly and mobile-friendly.

---

# 3. Technology Requirements

## 3.1 Backend

- Laravel 13.x.
- PHP 8.3 or newer, within the supported Laravel version range.
- MariaDB.
- Eloquent ORM.
- Laravel authentication and authorization.
- Laravel queues for asynchronous tasks.
- Laravel scheduler where required.

## 3.2 Frontend

- Blade templates.
- Hotwire Turbo.
- Turbo Drive.
- Turbo Frames.
- Turbo Streams.
- Stimulus controllers.
- Tailwind CSS v4.
- Preline UI.

## 3.3 Frontend Navigation Requirement

Hotwire Turbo is mandatory.

Same-origin navigation must use Turbo Drive by default.

Turbo Frames must be used for appropriate partial interactions, including:
- Search results.
- Catalog filters.
- Pagination.
- Modal content.
- Dashboard widgets.
- Inline editing where appropriate.

Turbo Streams must support server-driven UI updates where appropriate, especially:
- Lead activity updates.
- Admin notification interfaces.
- Dashboard data refreshes.

Do not use full browser reloads for ordinary internal navigation.

Full document navigation is permitted only when technically necessary, such as authentication transitions, file downloads, or explicitly excluded external destinations.

## 3.4 Explicitly Prohibited Frontend Alternatives

Do not replace the selected architecture with:
- Livewire.
- Inertia.js.
- React.
- Vue.
- Next.js.
- A separate SPA application.

Stimulus is permitted for focused client-side interactions.

---

# 4. Website Architecture

The application consists of two main interfaces sharing the same backend and database.

## 4.1 Public Website

- Homepage.
- Products.
- Product details.
- Installation packages.
- Package details.
- Services.
- CCTV installation.
- CCTV repair.
- About Us.
- Contact.
- Blog.
- Blog categories.
- Blog tags.
- Blog articles.
- Search.
- FAQ.

## 4.2 Admin Application

- Dashboard.
- Products.
- Brands.
- Product categories.
- Product taxonomies.
- Installation packages.
- Package categories.
- Services.
- Pages.
- Blog.
- Media library.
- Leads.
- Lead activities.
- Website analytics.
- WhatsApp analytics.
- Browser notifications.
- SEO management.
- Navigation management.
- Website settings.
- User management.
- Roles and permissions.
- Activity logs.

---

# 5. Public Website Requirements

## 5.1 Homepage

The homepage must communicate the service business immediately.

Required sections:

1. Hero and primary service positioning.
2. Installation and repair services.
3. Featured installation packages.
4. Equipment and brand references.
5. Company advantages based on verified information.
6. Installation process.
7. Service area information.
8. Blog highlights.
9. FAQ.
10. Final WhatsApp CTA.

Primary CTA: Konsultasi via WhatsApp.

Secondary CTA: Lihat Paket CCTV.

## 5.2 Product Catalog

URL: `/produk/`

Purpose: Display equipment information that supports CCTV installation decisions.

Features:
- Search.
- Brand filtering.
- Camera type filtering.
- Product category filtering.
- Relevant technical filters.
- Pagination.
- Product detail navigation.
- WhatsApp consultation.

Supported brands:
- Hikvision.
- Dahua.
- HiLook.
- VIGI.
- Tapo.
- Uniarch.
- EZVIZ.
- IMOU.

Only verified products and specifications may be published.

### Product Cards

Display:
- Image.
- Brand.
- Product name.
- Model.
- Camera type.
- Key specifications.
- Detail action.

No purchase functionality.

## 5.3 Product Detail

URL: `/produk/{slug}/`

Required content:
- Product image gallery.
- Brand.
- Product name.
- Model number.
- Description.
- Key features.
- Technical specifications.
- Recommended applications.
- Related products.
- Related installation packages.
- SEO metadata.
- Open Graph metadata.
- WhatsApp consultation CTA.

CTA: Konsultasikan Produk Ini.

WhatsApp message must include product name and URL.

## 5.4 Installation Package Catalog

URL: `/paket-cctv/`

Packages are the primary commercial offering.

Features:
- Camera count filtering.
- Usage category filtering.
- Installation category filtering.
- Search.
- Pagination.
- Package detail navigation.
- WhatsApp consultation.

Possible camera quantities:
- 1 camera.
- 2 cameras.
- 4 cameras.
- 8 cameras.
- 16 cameras.

Actual offerings must be verified before publication.

## 5.5 Package Detail

URL: `/paket-cctv/{slug}/`

Required content:
- Package title.
- Hero image.
- Description.
- Intended applications.
- Camera quantity.
- Included equipment.
- Installation scope.
- Relevant specifications.
- Installation process.
- Exclusions and notes.
- Related packages.
- FAQs.
- SEO metadata.
- Open Graph metadata.
- WhatsApp CTA.

CTA: Konsultasikan Paket Ini.

No cart, checkout, or online transaction.

Package prices are optional and must only be displayed when supplied and verified by the business.

## 5.6 Service Pages

Required:
- CCTV Installation.
- CCTV Repair.

Installation page must explain service scope, applications, installation process, and consultation.

Repair page must explain troubleshooting, inspection, repair capabilities, and relevant service limitations.

Service content must reflect actual business capabilities.

---

# 6. Blog System

Blog is a first-class feature, not an optional content section.

## 6.1 Blog Public Pages

- Blog index.
- Article detail.
- Category archive.
- Tag archive.
- Search results.
- Related articles.

Suggested URL structure:

- `/blog/`
- `/blog/{article-slug}/`
- `/blog/kategori/{category-slug}/`
- `/blog/tag/{tag-slug}/`

## 6.2 Blog Content

Each article supports:
- Title.
- Slug.
- Excerpt.
- Main content.
- Featured image.
- Author.
- Category.
- Tags.
- Publication status.
- Publication date.
- Updated date.
- Reading time.
- SEO title.
- Meta description.
- Canonical URL override.
- Open Graph title.
- Open Graph description.
- Open Graph image.
- Robots directives.

## 6.3 Blog Administration

Required features:
- Create.
- Edit.
- Preview.
- Draft.
- Publish.
- Schedule.
- Archive.
- Category management.
- Tag management.
- Featured image.
- SEO editing.

A rich content editor may be used, but content must remain accessible and SEO-friendly.

## 6.4 Blog Strategy

Content should support search intent around:
- CCTV installation.
- CCTV maintenance.
- CCTV troubleshooting.
- Camera technology.
- CCTV equipment selection.
- Security solutions.
- Residential CCTV.
- Commercial CCTV.

Internal links should connect articles to relevant services, products, and packages.

---

# 7. WhatsApp Conversion System

## 7.1 Global WhatsApp Configuration

A configurable WhatsApp number must be maintained through the admin settings.

## 7.2 Contextual CTA

Every major CTA must generate an appropriate prefilled message.

Product:

"Halo WP CCTV Indonesia, saya ingin konsultasi pemasangan menggunakan [Product Name]. Halaman: [URL]."

Package:

"Halo WP CCTV Indonesia, saya tertarik dengan [Package Name] dan ingin konsultasi instalasi. Halaman: [URL]."

Installation:

"Halo WP CCTV Indonesia, saya ingin konsultasi jasa instalasi CCTV."

Repair:

"Halo WP CCTV Indonesia, saya ingin konsultasi servis/perbaikan CCTV."

Blog:

"Halo WP CCTV Indonesia, saya ingin bertanya mengenai informasi pada artikel [Article Title]."

Messages must be URL-encoded correctly.

Do not send messages automatically.

## 7.3 WhatsApp Click Tracking

Every website-generated WhatsApp CTA must pass through a first-party tracking endpoint before redirecting to WhatsApp.

Record:
- Event ID.
- Event timestamp.
- Page URL.
- Page title.
- Page type.
- Product ID, package ID, service ID, or article ID where applicable.
- Referrer.
- UTM source.
- UTM medium.
- UTM campaign.
- Device category.
- Anonymous visitor/session identifier where consent and privacy rules permit.

Tracking must not unnecessarily collect personal information.

The system records a click event, not a confirmed WhatsApp conversation.

## 7.4 Semi-Automatic Lead Management

No mandatory pre-WhatsApp form.

A WhatsApp click creates an interaction record that may become a lead.

Admin can enrich the record with:
- Customer name.
- WhatsApp number.
- Lead type.
- Customer requirements.
- Status.
- Priority.
- Notes.
- Follow-up date.
- Assigned admin.

Lead statuses:
- New.
- Contacted.
- Qualified.
- Quotation.
- Negotiation.
- Won.
- Lost.
- Archived.

The system must distinguish:
- Anonymous WhatsApp click event.
- Identified lead.
- Confirmed customer.

Do not automatically assume that every click represents a unique person or actual conversation.

## 7.5 Lead Activity

Track:
- First interaction.
- Latest interaction.
- Source page.
- Related product or package.
- Admin updates.
- Status changes.
- Notes.
- Follow-up actions.

---

# 8. Browser Push Notifications

Browser notifications are mandatory for WhatsApp click events.

## 8.1 Purpose

Notify authorized admins when a visitor clicks a WhatsApp CTA on the public website.

Example:

"New WhatsApp Inquiry"

"Visitor clicked WhatsApp from Paket CCTV 4 Kamera."

## 8.2 Technical Requirements

Use the Web Push API and Service Worker.

Use VAPID authentication for server-to-browser push delivery.

Store push subscriptions securely and associate them with authenticated admin users.

The implementation must support:
- Subscription registration.
- Permission management.
- Service worker registration.
- Push event handling.
- Notification display.
- Notification click behavior.
- Subscription expiration.
- Subscription removal.
- Multiple admin devices.
- Notification delivery failure handling.

## 8.3 Notification Workflow

1. Visitor clicks a WhatsApp CTA.
2. Application records the click event.
3. Server dispatches an asynchronous notification job.
4. Server sends a push notification to subscribed admins.
5. Admin receives the browser notification.
6. Clicking the notification opens the relevant admin lead or activity record.

Notifications must not block the WhatsApp redirect.

## 8.4 Privacy and Permission

- Admin permission must be opt-in.
- Never request browser notification permission from public website visitors.
- Do not expose customer personal information in notification previews.
- Support notification preferences.
- Avoid duplicate notifications.
- Provide a way to disable subscriptions.
- Use HTTPS in production.
- Do not claim a notification was delivered merely because a push job was dispatched.

Foreground dashboard notifications may additionally use Turbo Streams.

---

# 9. Admin Dashboard

The dashboard is a CMS and business analytics platform.

## 9.1 Dashboard Overview

Display:
- Total website visitors.
- Page views.
- WhatsApp click events.
- Unique estimated visitors.
- Leads created.
- Lead conversion status.
- Most viewed products.
- Most viewed packages.
- Most popular articles.
- Traffic sources.
- Recent activities.
- Recent leads.

Date filters:
- Today.
- Yesterday.
- Last 7 days.
- Last 30 days.
- Last 90 days.
- Custom range.

All metrics must have clearly defined calculation rules.

## 9.2 Website Analytics

Track:
- Page views.
- Landing pages.
- Referrer sources.
- UTM campaigns.
- Device categories.
- Content engagement.
- Product views.
- Package views.
- Blog performance.

Do not treat page views as unique visitors.

## 9.3 WhatsApp Analytics

Track:
- Total WhatsApp clicks.
- Clicks by page type.
- Clicks by product.
- Clicks by package.
- Clicks by service.
- Clicks by article.
- Clicks by traffic source.
- Click trends over time.

## 9.4 Lead Management

Admin features:
- Lead list.
- Lead detail.
- Lead identification.
- Status management.
- Priority.
- Assignment.
- Notes.
- Follow-up dates.
- Activity timeline.
- Search and filtering.
- Export where authorized.

The lead system must not become a full CRM or transaction management system without additional approval.

---

# 10. SEO Architecture

SEO is a core architectural requirement.

## 10.1 Technical SEO

- Server-side rendered HTML.
- Crawlable internal links.
- Semantic HTML.
- Clean URL structure.
- XML sitemap.
- Robots.txt.
- Canonical URLs.
- Breadcrumbs.
- Correct HTTP status codes.
- Redirect management.
- Pagination handling.
- Image optimization.
- Structured data.
- Mobile-first indexing compatibility.

## 10.2 Metadata Management

Every indexable content type must support:
- SEO title.
- Meta description.
- Canonical URL.
- Robots directives.
- Open Graph title.
- Open Graph description.
- Open Graph image.
- Open Graph type.
- Twitter/X card metadata.

## 10.3 Open Graph

Open Graph is mandatory.

Every public content page must generate suitable OG metadata.

Supported content:
- Homepage.
- Products.
- Packages.
- Services.
- Static pages.
- Blog articles.
- Category archives where appropriate.

The admin must allow editing:
- OG title.
- OG description.
- OG image.

Fallback priority:
1. Explicit page-level OG setting.
2. Page-specific featured image.
3. Global default OG image.
4. Brand fallback image.

OG image must be an absolute URL and use suitable social-sharing dimensions.

Twitter/X metadata should use the corresponding OG information as a fallback unless overridden.

## 10.4 Structured Data

Implement appropriate structured data:
- Organization.
- LocalBusiness, where supported by verified business data.
- Service.
- Article.
- BreadcrumbList.
- WebSite.

Product structured data must not imply that individual CCTV equipment is available for online purchasing.

Do not generate misleading aggregate ratings or fabricated reviews.

## 10.5 SEO Administration

Admin must provide:
- Global SEO defaults.
- Default OG image.
- Per-page SEO editor.
- Per-product SEO editor.
- Per-package SEO editor.
- Per-service SEO editor.
- Blog SEO editor.
- Canonical override.
- Robots directives.
- Sitemap inclusion control.

---

# 11. Mobile Responsiveness

Both public website and admin are required to be mobile-friendly.

## Public Website

- Responsive navigation.
- Readable typography.
- Mobile-friendly product grids.
- Accessible filter controls.
- Responsive image galleries.
- Readable specification tables.
- Accessible WhatsApp CTAs.
- No horizontal overflow.

## Admin

- Responsive sidebar.
- Collapsible navigation.
- Mobile-friendly tables.
- Card-based alternatives where needed.
- Touch-friendly controls.
- Responsive charts.
- Usable forms.
- Accessible notifications.

Desktop and mobile are both first-class targets.

---

# 12. Content Management Architecture

Recommended content models:

- Products.
- Brands.
- Product categories.
- Product attributes.
- Installation packages.
- Package categories.
- Usage categories.
- Services.
- Pages.
- Blog posts.
- Blog categories.
- Blog tags.
- FAQs.
- Navigation menus.
- Media assets.
- SEO metadata.

Each content type must have explicit relationships and validation rules.

---

# 13. System Management

- Admin accounts.
- Roles and permissions.
- User profile.
- Activity logs.
- System settings.
- WhatsApp settings.
- SEO settings.
- Analytics settings.
- Browser notification subscriptions.
- Media management.

Suggested roles:
- Super Admin.
- Administrator.
- Content Editor.
- Lead Manager.
- Analyst.

Permissions must be granular and enforced server-side.

---

# 14. Non-Functional Requirements

## Performance

- Server-rendered public pages.
- Turbo navigation.
- Optimized database queries.
- Eager loading where appropriate.
- Image optimization.
- Asset bundling.
- Appropriate caching.
- Background queues for asynchronous tasks.
- No unnecessary JavaScript.

## Security

- CSRF protection.
- XSS prevention.
- SQL injection prevention.
- Authorization checks.
- Rate limiting.
- Secure sessions.
- Secure file uploads.
- Audit logging.
- Secure push subscription storage.

## Accessibility

- Semantic HTML.
- Keyboard navigation.
- Visible focus states.
- Sufficient contrast.
- Descriptive control labels.
- Accessible forms.
- Reduced-motion support.

---

# 15. Out of Scope

Unless separately approved:
- E-commerce.
- Shopping cart.
- Checkout.
- Payment gateways.
- Online product purchases.
- Shipping.
- Product inventory management.
- Order fulfillment.
- Automatic WhatsApp message reading.
- WhatsApp Business API integration.
- Full CRM.
- Accounting.
- Technician scheduling.
- Automated quotation and invoicing.

---

# 16. Acceptance Criteria

The project is accepted when:

1. Laravel 13 is running on the approved PHP version.
2. Public pages use server-rendered Blade.
3. Hotwire Turbo is used for internal navigation.
4. Turbo Frames and Streams work where required.
5. Preline UI is integrated consistently.
6. Preline Agent Skills are installed and available.
7. Preline Theme Generator is used for the brand theme.
8. Preline MCP is connected and usable.
9. Product and package catalogs work independently.
10. No e-commerce functionality exists.
11. Blog is fully manageable through admin.
12. WhatsApp click tracking works.
13. Anonymous click events are distinguishable from identified leads.
14. Admin can manage lead status and notes.
15. Browser notifications work for subscribed admins.
16. Notification delivery does not delay WhatsApp redirects.
17. Analytics display meaningful and correctly calculated metrics.
18. SEO metadata is editable.
19. Open Graph metadata is generated correctly.
20. XML sitemap and robots.txt work.
21. Public website and admin work on mobile.
22. Authorization and security tests pass.
23. Performance and regression tests pass.
24. No production publishing or deployment occurs without explicit approval.

---

# 17. Success Metrics

Primary:
- Qualified WhatsApp inquiries.
- Installation inquiries.
- Repair inquiries.
- Lead progression.

Secondary:
- Organic traffic.
- Organic landing page performance.
- Product engagement.
- Package engagement.
- Blog traffic.
- WhatsApp click-through rate.
- Lead identification rate.

A WhatsApp click is an engagement event, not proof of a successful conversation or sale.
