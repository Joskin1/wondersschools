FRONTEND MULTI-TENANCY CONTENT DYNAMICIZATION PRD PROMPT


You are a senior Laravel 12 + Filament v4 + stancl/tenancy v3 SaaS architect.



Project Context:

Originally built as single-tenant.

Now converted to multi-database tenancy.

All schools share the same frontend design template.

Only branding and content must differ per school.

Each school admin has a “Frontend Section” inside Filament where they can edit:

Hero section

Landing page sections

About page

Contact details

Images

Banners

Any visible frontend content



Goal:



Perform a full reverse-engineering audit of the frontend and ensure:

No hardcoded content remains.

All visible frontend text, headings, buttons, descriptions, images, metadata, titles, and footer content are dynamically loaded from the tenant database.

Each tenant sees only their own content.

All content is editable from the admin “Frontend Section”.

Proper seeding is implemented.

Tests are written.

PHASE 1 — FULL FRONTEND AUDIT


Scan and analyze:

resources/views/**

Blade components

Layout files

Page titles

Meta tags

Image src attributes

Hardcoded URLs

Static text blocks

Section headings

Button labels

Contact info

Footer content

Navigation labels



Produce:

A complete list of all hardcoded strings.

A list of hardcoded images.

A list of static metadata.

Any duplicated content across views.

Any frontend data not driven from database.



Do not modify yet.

Just produce an audit report.

PHASE 2 — DESIGN CONTENT ARCHITECTURE


Design a scalable per-tenant content system.



You may choose ONE of the following approaches and justify it:



A. settings table (key-value)

B. frontend_pages table

C. frontend_sections table

D. JSON-based structured content storage

E. Hybrid structured system



The system must support:

Hero title

Hero subtitle

Hero CTA button text + URL

About section

Mission/Vision

Feature blocks (repeater)

Gallery images

Testimonials (repeater)

Contact info

SEO meta title

SEO description

OpenGraph image

Footer text



Design:

Migration schema

Model structure

Relationship structure

Tenant-aware access

PHASE 3 — REFACTOR FRONTEND


Replace all hardcoded content with dynamic DB-driven calls.



Example transformation:



Before:

<h1>Welcome to Wonders Kiddies Foundation</h1>
After:

<h1>{{ $content->hero_title }}</h1>
For images:

Store in tenant storage

Use tenant filesystem disk

Use storage symlink properly



Ensure:

No direct Setting:: queries in views

Use view composers or a FrontendContentService

Avoid N+1 queries

PHASE 4 — ADMIN FRONTEND EDITOR PANEL


Inside Filament:



Create:



FrontendContentResource



Features:

Rich text editor

Repeater blocks

Image uploads

Live preview (optional)

Section grouping (Hero, About, Contact, SEO)



Ensure:

Only tenant admins can edit their content

Sudo cannot accidentally override tenant content unless explicitly switching tenancy

PHASE 5 — SEEDING


Create:



TenantFrontendSeeder



It must:

Seed realistic demo content

Seed different content per tenant

Seed image placeholders

Store images in correct tenant storage path

Demonstrate content isolation



When creating 2 tenants:



Tenant A:

Unique hero

Unique logo

Unique gallery



Tenant B:

Completely different content



Demonstrate separation clearly.

PHASE 6 — TESTING


Write tests for:

Tenant content isolation

Tenant A cannot see Tenant B content.

Content retrieval correctness

Admin edit persistence

Image storage correctness

Page rendering returns correct DB values

SEO metadata rendering

Tenant DB switching during frontend requests



Use:

Pest or PHPUnit

SQLite landlord override

Tenant DB creation in tests

PHASE 7 — PERFORMANCE REVIEW


Ensure:

No per-section queries inside Blade

No repeated Setting:: calls

All content loaded in one optimized query

Use caching if appropriate (tenant-aware cache)

REQUIRED OUTPUT FORMAT
Audit Report

Proposed Content Architecture

Database Schema

Model Definitions

Refactor Plan

Seeder Implementation

Test Suite

Risk Analysis

Rollback Plan



Think like a SaaS architect converting a single-tenant hardcoded marketing site into a scalable tenant-isolated dynamic content system.



Do not assume anything.

Analyze first.

Design cleanly.

Implement intentionally.