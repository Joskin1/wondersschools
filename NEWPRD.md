That's perfectly feasible. Consolidating the **Product Requirements Document (PRD)**, **Technical Specifications**, and all the new **Marketing Copy** into one cohesive file ensures a single source of truth for the project.

Here is the complete **Wonders Kiddies Foundation Schools (WKFS) Website PRD and Content Strategy** file, ready for copy and paste.

---

# 🚀 Complete Product Requirements Document (PRD) & Content Strategy

**Project Name:** Wonder Kiddies Foundation Schools (WKFS) Website
**Date:** November 29, 2025
**Version:** 2.0 (Consolidated Release)
**Owner:** Akinbomi OLuwadamilare
**Your Role:** Developer

## 1. 🎯 Project Overview & Goals

The goal is to build a modern, responsive, and easy-to-manage website for WKFS that leverages a **persuasive content flow** to guide prospective parents from discovery to enrollment. The site must reflect the school's brand identity and educational values as the primary digital enrollment engine.

### 1.1. Objectives (MVP)

1.  **Establish Digital Presence:** Launch a professional, branded website containing core information (Academics, Admissions, Contact).
2.  **Ease of Management:** Implement a powerful, intuitive admin panel (**Filament**) for school staff to manage content, news, and events without needing developer intervention.
3.  **Performance:** Build a fast, highly interactive website using the **TALL stack** approach.

## 2. 🛠️ Technical Specifications (The Foundation)

### 2.1. Required Tech Stack

The application must be built **exclusively** using the following technologies:

* **Backend Framework:** **Laravel** (latest stable version)
* **Frontend Interactivity:** **Full Page Livewire**
* **Admin Panel:** **Filament PHP** (latest stable version, used for all CRUD operations)
* **Styling:** **Tailwind CSS** (utility-first approach)
* **Client-Side Scripting:** **Alpine.js** (for simple UI state/dropdowns)

### 2.2. Architecture

* All public-facing pages must be implemented as **Full Page Livewire Components**.
* All user-facing content (except structural labels) **must** be dynamic (database-driven).
* All backend data management must be fully compatible with, and managed through, **Filament Resources and Pages**.

### 2.3. Database & Seeding

* **Seeder Requirement:** A comprehensive `DatabaseSeeder` utilizing Laravel **Faker** must populate all required tables (`Users`, `Posts`, `Staff`, `GalleryImages`, `Inquiries`, etc.) with sufficient mock data to fully test the site.

---

## 3. 🎨 Branding & Design Guide

### 3.1. Color Palette (Mandatory Implementation)

The design must heavily utilize the Primary Colours and use the Accent Colours strategically for CTAs and highlights.

| Name | Hex Code | Usage |
| :--- | :--- | :--- |
| **Lime Green** (Primary) | `#D9EF60` | Light backgrounds, highlights, secondary buttons. |
| **Dark Green** (Primary) | `#228B22` | **Primary buttons**, navigation bar, footer, key text (Main brand color). |
| **Red** (Accent) | `#D62828` | Danger states, alerts, or urgent communication. |
| **Light Blue** (Accent) | `#4EC5F1` | Interactive elements, links, and secondary highlights. |
| **White** (Neutral) | `#FFFFFF` | Main content background. |
| **Black** (Neutral) | `#000000` | Primary body text and headings. |

---

## 4. 📝 Content Flow & MVP Feature Breakdown

The website flow follows a high-conversion pattern: **Brand Story → Student Life → Academic Advantage → Conversion.**

### 4.1. Home Page (The Conversion Engine)

This page must be implemented as a **Full Page Livewire Component** pulling data from the database.

| Section Title | Flow Focus | Copy & Requirements |
| :--- | :--- | :--- |
| **Hero Section** | Brand Story / Hook | **Headline:** Wonders Kiddies Foundation Schools — **A Foundation That Builds Futures.** **Sub-headline:** We don't just teach children; we cultivate thinkers, leaders, and compassionate citizens in a secure, nurturing environment. **Primary CTA:** **Explore Our Curriculum** (Dark Green). **Secondary CTA:** **Book a Tour**. **Trust Strip:** ✅ Verified Curriculum • ✅ Experienced Educators • ✅ Secure Campus • ✅ Proven Results |
| **Why WKFS?** | The Brand Hook | **Title:** Why "Wonders"? Because Every Child is a World of Potential. **Body Copy:** Focus copy establishing the school's philosophy (e.g., child-centric learning, foundational skills). |
| **School Life** | Lifestyle Promise | **Title:** More Than a Classroom. A Place Your Child Can Thrive. **Content:** Display the **three most recent News/Event posts** (Data via Filament). |
| **Meet the Leadership** | Trust + Proof | **Title:** Our Commitment: Experienced Hands, Nurturing Hearts. **Content:** Profiles of the Head of School and key leaders (Pulled from a **Staff Filament Resource**). |
| **Final CTA Strip** | Final Conversion | **Headline:** Ready for the WKFS Foundation? **Buttons:** Book a Tour | Enrol Now (Links to Admissions Form) | Chat on WhatsApp |

### 4.2. About Us Page (Mission & Trust)

**Headline:** We Build Foundations That Last.

**Body:**
Wonders Kiddies Foundation Schools (WKFS) is dedicated to providing a high-quality, nurturing, and secure educational environment. Our approach is simple: we focus on the **whole child**—intellectually, emotionally, and morally—to ensure they thrive in every aspect of life. We believe a strong foundation in the early years is the rarest, most valuable asset a parent can provide.

**Mission:**
To deliver secure, well-planned education that fosters creativity, academic mastery, and strong character development.

**Vision:**
To be the most trusted educational brand known for foundational excellence, transparency, and dependable long-term student success.

**Core Values:**
* Integrity of Instruction
* Student-Centric Nurturing
* Strategic Curriculum Delivery
* Transparent Parent Partnership
* Long-term Value Creation

### 4.3. Academics / Curriculum Page (The WKFS Advantage)

**Headline:** The WKFS Advantage: A Foundation That Outlasts Trends.

**Body:**
A child's future is defined by the quality of their foundation. At Wonders Kiddies Foundation Schools (WKFS), our curriculum is strategically designed not just to meet required standards, but to **exceed them** by cultivating critical thinking, creativity, and essential life skills. We combine a solid core curriculum with modern, integrated learning methods to ensure every student is prepared not just for the next class, but for a fast-changing world.

#### Structured Learning Levels

| Level | Focus & Key Outcome |
| :--- | :--- |
| **Early Years Foundation Stage (EYFS)** | **Focus:** Play-based learning, sensory exploration, and developing early literacy and numeracy. **Outcome:** Building curiosity, fine motor skills, and social-emotional readiness. |
| **Primary School Programme** | **Focus:** Mastery of core subjects (Numeracy, Literacy, Science) combined with integrated studies (STEM, Coding Introduction). **Outcome:** Fostering independence, research skills, and strong problem-solving abilities. |

#### Subject Highlights: Building Mastery

| Subject Area | WKFS Approach & Advantage |
| :--- | :--- |
| **Literacy & Communication** | We emphasize reading for comprehension and creative writing. Students learn not just *what* to read, but *how* to analyze, articulate, and present their ideas confidently. |
| **Numeracy & Logic** | Moving beyond rote arithmetic, we use hands-on, conceptual learning to build strong mathematical reasoning. Our students learn to apply logic to real-world problems. |
| **Integrated Science (STEM)** | Science is taught through practical experimentation and inquiry, preparing students for future tech and engineering fields. |
| **Character & Ethics** | Robust training in core values, empathy, leadership, and responsibility, ensuring your child grows into a well-rounded and compassionate individual. |

### 4.4. Admissions Page (How to Join)

**Headline:** Secure a Brighter Start in Just Three Steps.

**Body:**
We’ve streamlined our admissions process to be transparent, simple, and supportive. We guide you through inspection, documentation, payment, and placement step-by-step.

**Step-by-Step Process:**
1.  **Book an Inspection/Tour:** Schedule a time to see the WKFS environment and meet our staff.
2.  **Application & Assessment:** Complete the application form and your child takes a simple age-appropriate assessment.
3.  **Payment & Placement:** Secure your child’s spot by completing the fee payment (refer to the Fee Schedule, managed via Filament).

**Fee Schedule Section:**
* A simple, legible table of current school fees (Data managed via a **Filament Resource** or **Settings Page**).
* **CTA:** Download the Full Fee Schedule (PDF link managed via Filament Settings).

**Admissions Inquiry Form:**
* Fields: Name, Email, Phone, Child's Age, Desired Class.
* **Submission:** Data submits directly to a dedicated **Filament Resource (Inquiry)**.

### 4.5. News, Gallery, and Contact Pages

| Page Name | Required Features (All Livewire Components) | Filament Data Source |
| :--- | :--- | :--- |
| **News & Events** | Paginating listing page with title, date, excerpt. Full detail page for each post. | **Post Resource** |
| **Gallery** | Responsive photo grid. Must include a filtering function by Category (e.g., Sports Day, Field Trips). | **GalleryImage Resource** |
| **Contact Us** | Embedded Google Map, Full Address, Phone/Email (from Settings). **Contact Form** (Name, Email, Message). | **ContactSubmission Resource** |

---

## 5. ⚙️ Admin Panel (Filament PHP Resources)

The Admin Panel must enable staff to manage the site content without developer intervention.

| Model / Feature | Description | Required Filament Component |
| :--- | :--- | :--- |
| **Users** | Management of Admin users (e.g., Admin, Content Manager roles). | **Filament Resource (User)** |
| **News & Events** | CRUD for school announcements/events (Title, Body-Rich Text, Date, Featured Image). | **Filament Resource (Post)** |
| **Admissions Inquiries** | View and manage submissions from the Admissions Inquiry Form. | **Filament Resource (Inquiry)** |
| **Contact Submissions** | View and manage submissions from the Contact Us Form. | **Filament Resource (ContactSubmission)** |
| **Leadership/Staff** | CRUD for staff profiles: Name, Title/Role, Photo, Biography. | **Filament Resource (Staff)** |
| **Gallery** | CRUD for managing image uploads: Image file and **Category** (Select/Relationship field). | **Filament Resource (GalleryImage)** |
| **Settings** | Manage global variables: School Phone Number, Email, Address, Fee Schedule PDF Link. | **Filament Page (Settings)** |

---

## 6. 📝 Microcopy & CTAs (Standardization)

Ensure consistent, conversion-focused language across all buttons and forms.

| Category | Example Copy |
| :--- | :--- |
| **Primary Buttons** | **Book a Tour Today** (Dark Green), **Enrol Now**, **Start Payment Plan** |
| **Secondary Buttons** | Explore Our Curriculum, Download Brochure, View Price List, Talk to an Advisor |
| **Forms** | "Which class interests you?", "Best time to call you?", "Physical or virtual inspection?" |