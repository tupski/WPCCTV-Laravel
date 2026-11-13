# DESIGN.md
## WP CCTV Indonesia — Design System

**Brand:** WP CCTV Indonesia  
**Tagline:** SMART SECURITY SOLUTIONS  
**Application:** Laravel 13  
**Frontend:** Blade + Hotwire Turbo + Stimulus  
**UI Framework:** Preline UI  
**CSS:** Tailwind CSS v4  
**Primary Conversion:** WhatsApp

---

# 1. Design Direction

WP CCTV Indonesia must look like a professional CCTV installation and security solutions company.

The website must communicate:

- Professionalism.
- Technical competence.
- Reliability.
- Trust.
- Practical solutions.
- Clear service offerings.

The admin must feel like a professional business management application.

Avoid generic marketplace aesthetics.

Avoid generic SaaS dashboard aesthetics when they do not support actual business workflows.

## Core Principles

1. Service-first.
2. Clear information hierarchy.
3. Professional photography.
4. Restrained visual decoration.
5. Consistent spacing.
6. Accessible interactions.
7. Strong readability.
8. Conversion-oriented layouts.
9. Mobile-first responsiveness.
10. Reusable Preline components.

---

# 2. Brand Theme

Preline Theme Generator is the required source for generating the project's custom theme.

Do not maintain an unrelated manual color system.

## Brand Colors

| Token | Hex | Usage |
|---|---|---|
| Brand Navy | #102A43 | Brand identity and major headings |
| Primary Blue | #1D4ED8 | Primary actions |
| Accent Blue | #2EA3F2 | Supporting accents |
| Light Blue | #D9E9FC | Subtle surfaces |
| Text | #334155 | Main text |
| Muted | #64748B | Secondary text |
| Border | #E2E8F0 | Borders and dividers |
| Background | #F8FAFC | Alternate backgrounds |
| Surface | #FFFFFF | Main surfaces |

## Color Rules

- Navy establishes the identity.
- Blue is used for important actions.
- Light blue provides subtle emphasis.
- White provides visual breathing room.
- WhatsApp green is reserved for WhatsApp-related actions.
- Red indicates destructive or critical states.
- Amber indicates warnings.
- Green indicates success.

Avoid:
- Excessive gradients.
- Neon accents.
- Glowing buttons.
- Multiple competing accent colors.
- Unnecessary dark backgrounds.

---

# 3. Preline UI Design Rules

Preline UI is the primary component system for public and admin interfaces.

Use:
- Preline components.
- Preline blocks.
- Preline layouts.
- Preline JavaScript plugins.
- Generated brand theme.

Use the Preline MCP to discover real components and complete blocks.

Do not fabricate component IDs.

Do not assume component APIs.

Read component documentation when using interactive plugins.

## Theme Consistency

The following must share the same design tokens:
- Public website.
- Admin navigation.
- Admin dashboard.
- Forms.
- Tables.
- Modals.
- Alerts.
- Notifications.

Public and admin may use different layout compositions while retaining one brand design system.

---

# 4. Typography

Primary font: Montserrat.

Suggested hierarchy:

| Element | Desktop | Mobile |
|---|---|---|
| H1 | 44–56px | 32–38px |
| H2 | 32–40px | 26–32px |
| H3 | 22–28px | 20–24px |
| H4 | 18–22px | 18–20px |
| Body | 15–17px | 14–16px |
| Caption | 12–14px | 12–13px |

Line height:
- Headings: 1.15–1.3.
- Body: 1.55–1.75.

Avoid oversized typography that reduces content clarity.

---

# 5. Public Website Layout

## Global Container

- Main width: approximately 1200px.
- Wide sections: approximately 1320px.
- Reading width: approximately 760px.

## Section Spacing

Desktop:
- Standard: 80–104px.
- Compact: 48–64px.

Mobile:
- Standard: 48–64px.
- Compact: 32–40px.

Maintain consistent vertical rhythm.

---

# 6. Public Header

Desktop:
- Logo on the left.
- Navigation.
- WhatsApp CTA on the right.
- White background.
- Subtle border.
- Clear active state.

Navigation:
- Beranda.
- Produk.
- Paket CCTV.
- Layanan.
- Blog.
- Tentang Kami.
- Kontak.

Mobile:
- Compact header.
- Responsive navigation drawer.
- Accessible menu controls.
- Convenient WhatsApp access.

Use Turbo navigation for internal links.

External WhatsApp destinations must use normal external navigation.

---

# 7. Homepage

The homepage must establish the service business immediately.

## Section 1 — Hero

Desktop:
- Two-column layout.
- Text on the left.
- Professional installation image on the right.

Headline:

"Solusi CCTV untuk Keamanan Rumah dan Bisnis"

Supporting text:

"WP CCTV Indonesia menyediakan layanan instalasi dan servis CCTV untuk kebutuhan rumah, toko, kantor, dan berbagai area usaha."

Primary CTA:
"Konsultasi via WhatsApp"

Secondary CTA:
"Lihat Paket CCTV"

## Section 2 — Main Services

Two prominent cards:

### Instalasi CCTV

Communicate professional installation solutions and consultation.

### Servis CCTV

Communicate troubleshooting, inspection, and repair.

## Section 3 — Featured Packages

Show installation package cards.

Each card:
- Package image.
- Package name.
- Camera quantity.
- Intended application.
- Brief scope.
- Detail action.

Never display shopping controls.

## Section 4 — Brands

Introduce supported equipment brands.

Use actual brand logos and avoid visual clutter.

## Section 5 — Why Choose Us

Use verified business advantages.

## Section 6 — Installation Process

1. Konsultasi.
2. Analisis kebutuhan.
3. Rekomendasi solusi.
4. Penawaran.
5. Instalasi.
6. Pemeriksaan sistem.

Only publish processes that reflect actual operations.

## Section 7 — Blog Highlights

Show recent educational articles.

Use descriptive titles and relevant featured images.

## Section 8 — FAQ

Answer common installation, repair, storage, and equipment questions.

## Section 9 — Final CTA

"Butuh Solusi CCTV untuk Rumah atau Bisnis Anda?"

CTA: Konsultasi Sekarang.

---

# 8. Product Catalog

URL: `/produk/`

Purpose: Equipment reference catalog.

## Layout

Desktop:
- Search and filters.
- Product grid.

Mobile:
- Search field.
- Filter drawer or modal.
- Responsive product grid.

Filters:
- Brand.
- Camera type.
- Product category.
- Relevant technical attributes.

Use progressive filtering.

## Product Card

Display:
- Product image.
- Brand.
- Product name.
- Model.
- Key specification.
- Detail action.

Cards must feel like technical references rather than merchandise listings.

No:
- Price badges suggesting online sales.
- Shopping cart icons.
- Add to Cart.
- Buy Now.
- Quantity controls.

---

# 9. Product Detail

URL: `/produk/{slug}/`

## Hero

Desktop:
- Product image or gallery on the left.
- Product information on the right.

Mobile:
- Image first.
- Product information second.
- CTA clearly visible.

Display:
- Brand.
- Product name.
- Model.
- Short description.
- Key specifications.
- Consultation CTA.

## Features

Use concise feature sections.

## Technical Specifications

Use an accessible specification table.

Hide unavailable specifications.

## Applications

Explain actual use cases supported by the product.

## Related Installation Packages

Connect equipment references with installation services.

## Final CTA

"Konsultasikan Produk Ini untuk Kebutuhan Instalasi Anda"

CTA: Konsultasikan Produk Ini.

---

# 10. Installation Package Catalog

URL: `/paket-cctv/`

This is the primary commercial catalog.

## Hero

Headline:

"Paket Instalasi CCTV untuk Rumah dan Bisnis"

Supporting text:

"Temukan solusi instalasi CCTV berdasarkan jumlah kamera dan kebutuhan lokasi Anda."

CTA: Konsultasi Instalasi.

## Filters

- Jumlah kamera.
- Kategori penggunaan.
- Jenis instalasi.

## Package Cards

Display:
- Installation image.
- Package name.
- Camera quantity.
- Intended application.
- Key inclusions.
- Detail action.

The package card must communicate a service offer.

Optional price display is permitted only when verified and clearly scoped.

---

# 11. Package Detail

URL: `/paket-cctv/{slug}/`

This page must communicate the actual installation solution.

## Section 1 — Hero

Headline example:

"Paket Instalasi CCTV 4 Kamera"

Supporting text:

"Solusi pengawasan untuk rumah, toko, dan ruang usaha dengan kebutuhan beberapa titik pemantauan."

CTA: Konsultasikan Paket Ini.

## Section 2 — Package Overview

Explain:
- Intended customer.
- Typical applications.
- Main benefits.
- Installation considerations.

## Section 3 — Included Equipment

Show included equipment as part of the service solution.

Each item:
- Product image.
- Brand.
- Model.
- Function.
- Key specifications.

This is not a shopping cart breakdown.

## Section 4 — Installation Scope

Explain:
- Camera installation.
- Cabling, where included.
- Recording equipment configuration, where included.
- System testing.
- Other verified work.

Explicitly identify exclusions.

## Section 5 — Installation Process

Present a clear process.

## Section 6 — Related Packages

Show relevant alternatives.

## Section 7 — FAQ

Package-specific questions.

## Section 8 — Final CTA

"Ingin Menggunakan Paket Ini?"

CTA: Konsultasikan Paket Ini.

---

# 12. Service Pages

## Installation

Prioritize:
- Service explanation.
- Installation applications.
- Equipment selection.
- Process.
- Package recommendations.
- FAQ.
- WhatsApp consultation.

## Repair

Prioritize:
- Common CCTV problems.
- Troubleshooting capabilities.
- Inspection.
- Repair considerations.
- Limitations.
- WhatsApp consultation.

Use authentic, professional installation and technician photography.

---

# 13. Blog Design

Blog must feel like a professional technical knowledge center.

## Blog Index

Required:
- Featured article.
- Latest articles.
- Category navigation.
- Search.
- Article cards.
- Pagination.

## Article Card

- Featured image.
- Category.
- Article title.
- Excerpt.
- Publication date.
- Reading time.
- Read more.

## Article Detail

Layout:
- Breadcrumb.
- Article category.
- Title.
- Excerpt.
- Author and publication information.
- Featured image.
- Main article content.
- Table of contents when useful.
- Related articles.
- Relevant product or package links.
- WhatsApp CTA.

Maintain a comfortable reading width.

Avoid excessive sidebar content.

Use proper heading hierarchy.

---

# 14. WhatsApp CTA

WhatsApp is the primary conversion action.

## CTA Labels

| Context | Label |
|---|---|
| Header | Hubungi Kami |
| Homepage | Konsultasi Sekarang |
| Product | Konsultasikan Produk Ini |
| Package | Konsultasikan Paket Ini |
| Installation | Konsultasi Instalasi |
| Repair | Konsultasi Servis |
| Blog | Tanya Lebih Lanjut |

Use a recognizable WhatsApp icon.

Buttons must have sufficient contrast and touch area.

Mobile:
- Optional floating WhatsApp action.
- Avoid overlapping content.
- Avoid covering important navigation.
- Maintain adequate bottom spacing.

---

# 15. Admin Design

The admin is a business management application, not simply a collection of CRUD forms.

Use a professional Preline application/dashboard layout.

## 15.1 Admin Shell

Desktop:
- Sidebar navigation.
- Top navigation bar.
- Breadcrumbs.
- Main content area.
- Notification center.
- User menu.

Mobile:
- Collapsible navigation drawer.
- Compact header.
- Touch-friendly controls.
- Responsive content.

Avoid excessive dashboard decoration.

## 15.2 Dashboard Overview

Recommended visual hierarchy:

### KPI Summary

- Visitors.
- Page views.
- WhatsApp clicks.
- Identified leads.
- Qualified leads.

### Traffic Analytics

- Visitors over time.
- Traffic sources.
- Device distribution.

### Conversion Analytics

- WhatsApp clicks by page.
- Top product pages.
- Top package pages.
- Top blog articles.

### Lead Overview

- New leads.
- Follow-up.
- Qualified.
- Won.
- Lost.

### Recent Activity

- Latest WhatsApp clicks.
- Latest lead updates.
- Recent content publication.

Use charts only where they help interpret data.

Do not turn every number into a chart.

---

# 16. Admin Navigation

Recommended structure:

### Overview
- Dashboard.

### Content
- Pages.
- Products.
- Brands.
- Packages.
- Services.
- Blog.
- Categories.
- Tags.
- FAQs.
- Media Library.
- Navigation Menus.

### Business
- WhatsApp Activity.
- Leads.
- Follow-ups.

### Analytics
- Website Analytics.
- Content Performance.
- WhatsApp Analytics.
- Campaign Reports.

### Notifications
- Browser Notifications.
- Notification Preferences.

### Settings
- General.
- WhatsApp.
- SEO.
- Open Graph.
- Analytics.
- Users.
- Roles & Permissions.
- Activity Logs.

Navigation must respect user permissions.

---

# 17. Admin Tables and Forms

Tables:
- Clear column hierarchy.
- Search.
- Filters.
- Pagination.
- Sort controls where useful.
- Bulk actions where appropriate.
- Responsive mobile alternatives.

Forms:
- Logical field grouping.
- Clear labels.
- Validation messages.
- Helpful descriptions.
- Appropriate input types.
- Save feedback.
- Unsaved-change awareness where appropriate.

Avoid enormous forms with unrelated fields.

SEO fields should be grouped separately from primary content fields.

---

# 18. Lead Management Interface

## Lead List

Display:
- Lead status.
- Customer name, if identified.
- WhatsApp number, if identified.
- Inquiry type.
- Source page.
- Latest interaction.
- Assigned admin.
- Follow-up date.

Anonymous click events must be visibly distinguished from identified leads.

## Lead Detail

Display:
- Lead summary.
- Interaction history.
- Source information.
- Related product/package.
- Status history.
- Notes.
- Follow-up.
- Admin assignment.

The design must make follow-up easy.

---

# 19. Browser Notifications

Use browser notifications for admin awareness.

## Dashboard Notification Center

Display:
- New WhatsApp clicks.
- Recent notifications.
- Notification history.
- Subscription status.

## Browser Permission UI

Provide a clear opt-in action.

Do not automatically trigger the browser permission dialog when an admin first visits the site.

Explain why permission is required.

## Notification Example

Title:

"New WhatsApp Inquiry"

Body:

"Visitor clicked WhatsApp from Paket CCTV 4 Kamera."

Click action:

Open the relevant admin activity.

Do not expose unnecessary customer data.

---

# 20. SEO and Social Sharing

Every public page must have appropriate metadata.

The admin SEO editor must support:
- SEO title.
- Meta description.
- Canonical.
- Robots.
- OG title.
- OG description.
- OG image.
- Twitter/X title.
- Twitter/X description.
- Twitter/X image.

Provide a visual preview of social sharing where practical.

Use a clear fallback hierarchy.

## OG Image Direction

Default image:
- WP CCTV Indonesia brand.
- Navy and blue palette.
- Logo.
- Descriptive content title when appropriate.
- Consistent social-sharing composition.

Recommended default output dimensions: 1200 × 630 pixels.

Page-specific OG images take precedence over global defaults.

---

# 21. Mobile Responsiveness

## Public Website

Verify:
- Navigation.
- Hero.
- Product grids.
- Filters.
- Image galleries.
- Specification tables.
- Package sections.
- Blog reading.
- WhatsApp CTA.

## Admin

Verify:
- Sidebar.
- Dashboard.
- Tables.
- Charts.
- Forms.
- Modals.
- Lead details.
- Notifications.

Mobile must not be a reduced afterthought.

Use responsive layout patterns rather than simply shrinking desktop elements.

---

# 22. Interaction and Animation

Allowed:
- Subtle hover.
- Lightweight transitions.
- Accessible accordions.
- Functional dropdowns.
- Modal dialogs.
- Carousels where useful.
- Turbo transitions where appropriate.

Avoid:
- Excessive parallax.
- Continuous decorative animations.
- Glowing elements.
- Unnecessary page entrance effects.
- Heavy JavaScript interactions.
- Interactions that interfere with Turbo navigation.

All interactions must remain usable after Turbo navigation and partial rendering.

---

# 23. Photography

Use realistic CCTV-related photography.

Preferred:
- Actual CCTV installations.
- Residential camera installations.
- Commercial buildings.
- Security monitoring environments.
- Technicians installing cameras.
- DVR/NVR equipment.
- Industrial security applications.

Product imagery must accurately represent the relevant model.

Package imagery must communicate installation applications.

Avoid:
- Misleading equipment configurations.
- Obvious AI image artifacts.
- Inconsistent image styles.
- Irrelevant generic stock photos.

---

# 24. Accessibility and Performance

- Semantic HTML.
- Accessible controls.
- Keyboard navigation.
- Visible focus.
- Adequate contrast.
- Responsive images.
- Optimized media.
- Reduced-motion support.
- Minimal unnecessary JavaScript.
- Efficient component initialization.

---

# 25. Design Acceptance Criteria

The design is accepted when:

1. The public site communicates CCTV installation and repair services.
2. Product pages look informational.
3. Package pages look like installation service offers.
4. Blog is comfortable to read.
5. WhatsApp actions are clear and accessible.
6. Admin is suitable for daily business management.
7. Analytics are easy to understand.
8. Lead status is visually distinguishable.
9. Browser notifications are manageable.
10. SEO and OG editing are understandable.
11. Public and admin layouts work on mobile.
12. Preline components remain visually consistent.
13. Turbo navigation does not break interactions.
14. The interface avoids generic AI-generated aesthetics.

---

# 26. Final Design Principle

WP CCTV Indonesia must look and behave like a professional security solutions company with a modern digital business platform.

The public website attracts and educates customers.

The product catalog explains equipment.

The package catalog communicates installation solutions.

The blog builds search visibility and credibility.

WhatsApp converts interest into inquiries.

The admin dashboard measures engagement and helps manage follow-up.

Every design decision must reinforce this business model.
