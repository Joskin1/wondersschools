Product Requirements Document (PRD): Wonders Kiddies Foundation Schools (WKFS) Website

Project Name: Wonder Kiddies Foundation Schools (WKFS) Website
Date: November 28, 2025
Version: 1.1 (MVP Updated)
Owner: Akinbomi OLuwadamilare
Your Name/Role: Developer



## 1. Project Overview & Goals

The goal of this project is to build a modern, responsive, and easy-to-manage website for Wonders Kiddies Foundation Schools (WKFS). The site will serve as the primary digital touchpoint for prospective parents, current parents, and the wider com$$## 1. Project Overview & Goals

The goal of this project is to build a modern, responsive, and easy-to-manage website for Wonders Kiddies Foundation Schools (WKFS). The site will serve as the primary digital touchpoint for prospective parents, current parents, and the wider community, reflecting the school's brand identity and educational values.

### 1.1. Objectives (MVP)

1. **Establish Digital Presence:** Launch a professional, branded website containing core information (Academics, Admissions, Contact).

2. **Ease of Management:** Implement a powerful, intuitive admin panel (Filament) for school staff to manage content, news, and events without needing developer intervention.

3. **Responsiveness:** Ensure a seamless user experience across all devices (mobile, tablet, desktop).

4. **Performance:** Build a fast, highly interactive website using the TALL stack approach.

### 1.2. Target Audience

* **Primary:** Prospective parents seeking information about admission, curriculum, and school values (conversion focus).

* **Secondary:** Current parents seeking school news, event schedules, and contact information.

* **Tertiary:** School staff for content management and updates.

## 2. Branding & Design Guide

### 2.1. Brand Identity

The website must align visually with the "Wonders Kiddies Foundation Schools" brand, focusing on bright, engaging, and professional aesthetics appropriate for an educational institution.

### 2.2. Color Palette (Mandatory Implementation)

The design must heavily utilize the Primary Colours and use the Accent Colours strategically for calls-to-action (CTAs), alerts, and key highlights.

| Name | Hex Code | Usage | 
 | ----- | ----- | ----- | 
| **Lime Green** (Primary) | `#D9EF60` | Light backgrounds, highlights, secondary buttons. | 
| **Dark Green** (Primary) | `#228B22` | Primary buttons, navigation bar, footer, key text elements (the main brand color). | 
| **Red** (Accent) | `#D62828` | Danger states, alerts, or urgent communication. | 
| **Light Blue** (Accent) | `#4EC5F1` | Interactive elements, links, and secondary highlights. | 
| **White** (Neutral) | `#FFFFFF` | Main content background. | 
| **Black** (Neutral) | `#000000` | Primary body text and headings. | 

## 3. Technical Specifications

### 3.1. Required Tech Stack

The entire application must be built exclusively using the following technologies:

* **Backend Framework:** Laravel (latest stable version)

* **Frontend Interactivity:** Full Page Livewire

* **Admin Panel:** Filament PHP (latest stable version, used for all CRUD operations)

* **Styling:** Tailwind CSS (utility-first approach)

* **Client-Side Scripting:** Alpine.js (for simple UI state/dropdowns)

### 3.2. Architecture

* All public-facing pages must be implemented as **Full Page Livewire Components**.

* All backend data management (models, migrations, relationships) must be fully compatible with, and managed through, **Filament Resources and Pages**.

### 3.3. Database & Seeding (New Requirement)

* **Seeder Requirement:** A comprehensive `DatabaseSeeder` must be provided. This seeder must utilize the Laravel Faker library to populate all required tables (`Users`, `Posts`, `Staff`, `GalleryImages`, `Inquiries`, etc.) with mock, descriptive data.

* **Testing Data:** The seeded data must be sufficient to fully test all frontend public pages (ensuring listings like News and Gallery pages are populated) and all Filament Resources without requiring manual data entry post-installation.

## 4. Minimum Viable Product (MVP) Feature Breakdown

### 4.1. Public Facing Pages (Frontend)

All pages must be fully responsive and utilize the defined color palette.

| Page Name | Type | Key Features & Requirements | 
 | ----- | ----- | ----- | 
| **Home Page** | Livewire Component | 1\. **Hero Section:** Large banner with a strong Call-to-Action (e.g., "Enrol Now" button using Dark Green). 2. **Welcome Message:** A brief, compelling message from the Head of School. 3. **Quick Links:** Cards linking to Admissions, Academics, and Contact pages. 4. **Latest News Preview:** Display the three most recent News/Event posts. | 
| **About Us** | Livewire Component | 1\. **Mission, Vision & Values:** Clear display of the school's core principles. 2. **School History:** A brief timeline or summary of the school's journey. 3. **Meet the Leadership:** Profiles of the Head of School and key leaders (Pulled from Filament). | 
| **Academics / Curriculum** | Livewire Component | 1\. **Curriculum Overview:** Description of the educational approach (e.g., early years, primary level structure). 2. **Classes/Levels:** List of available classes (e.g., Nursery, KG1, Primary 1-6). 3. **Subject Highlights:** Brief sections detailing key subjects (Literacy, Numeracy, Science). | 
| **Admissions** | Livewire Component | 1\. **Step-by-Step Process:** Clear outline of the application process. 2. **Fee Schedule:** A simple, legible table of current school fees (managed via Filament). 3. **Inquiry Form:** A simple form (Name, Email, Phone, Child's Age) that submits data to a dedicated table (managed via Filament). | 
| **News & Events** | Livewire Component | 1\. **Listing Page:** Display all news/event posts with pagination. Each item must show title, date, and a brief excerpt. 2. **Detail Page:** A full page to view a single news item, including the full body text and associated media. | 
| **Gallery** | Livewire Component | 1\. **Photo Grid:** A responsive grid displaying school photos. 2. **Categorization:** Ability to filter photos by category (e.g., Sports Day, Graduation, Field Trips - categories managed via Filament). | 
| **Contact Us** | Livewire Component | 1\. **Location:** Embedded Google Map with school location. 2. **Contact Details:** Full address, phone numbers, and email addresses. 3. **Contact Form:** A direct form for general inquiries (similar to Admissions form, submitting to a separate table managed via Filament). | 

### 4.1.1. Dynamic Content Strategy (Updated Requirement)

* **Database-Driven Content:** All user-facing content that is expected to change after launch—including text (welcome messages, mission statements, news bodies), asset URLs (images, PDFs), and data listings (staff, news, gallery)—**must** be stored in the database and pulled as variables into the Livewire Blade components. Hardcoded static text should be limited only to navigational elements or structural labels.

* **Generic Placeholders:** For initial development and testing, all image placeholders in the Livewire Blade components must use generic, dynamic placeholder image URLs (e.g., services like `https://placehold.co/` or `LoremFlickr`) or be pulled from a seeded, generic image in the storage folder. No production-specific images or locally hardcoded image paths should be used in the views.

### 4.2. Admin Panel (Filament PHP)

The Admin Panel must be fully secured and accessible only to authenticated school staff. All models must have corresponding Filament Resources to allow non-developer content management.

| Model / Feature | Description | Required Filament Components | 
 | ----- | ----- | ----- | 
| **Users** | Management of Admin users with basic roles (e.g., Admin, Content Manager). | Filament Resource (User) | 
| **News & Events** | CRUD for school announcements and events. Must include fields for Title, Body (Rich Text Editor), Date, and optional Featured Image. | Filament Resource (Post) | 
| **Admissions Inquiries** | View and manage submissions from the Admissions Inquiry Form. | Filament Resource (Inquiry) | 
| **Contact Submissions** |  andView and manage submissions from the Contact Us Form. | Filament Resource (ContactSubmission) | 
| **Leadership/Staff** | CRUD for staff profiles: Name, Title/Role, Photo, Biography. | Filament Resource (Staff) | 
| **Gallery** | CRUD for managing image uploads. Must include fields for Image file and Category (Select/Relationship field). | Filament Resource (GalleryImage) | 
| **Settings** | A basic Filament Page to manage global variables, such as: School Phone Number, Email, Address, Fee Schedule PDF Link. | Filament Page (Settings) | 

## 5. Non-Functional Requirements (NFRs)

* **Performance:** All Livewire components must be optimized for fast loading times.

* **Security:** Adhere to Laravel's security best practices (CSRF protection, input validation). Filament's access controls must be correctly configured.

* **Code Quality:** Use clear, commented, and standard Laravel/Livewire/Filament code practices.

* **Accessibility:** Adhere to basic WCAG guidelines (clear contrast, keyboard navigation for interactive elements).

## 6. Out of Scope (Future Consideration)

The following features are *not* part of the MVP:

* User accounts for parents/students (parent portal).

* Online payment processing.

* Multi-language support.

* Complex animations or 3D graphics.$$munity, reflecting the school's brand identity and educational values.

### 1.1. Objectives (MVP)

1. **Establish Digital Presence:** Launch a professional, branded website containing core information (Academics, Admissions, Contact).

2. **Ease of Management:** Implement a powerful, intuitive admin panel (Filament) for school staff to manage content, news, and events without needing developer intervention.

3. **Responsiveness:** Ensure a seamless user experience across all devices (mobile, tablet, desktop).

4. **Performance:** Build a fast, highly interactive website using the TALL stack approach.

### 1.2. Target Audience

* **Primary:** Prospective parents seeking information about admission, curriculum, and school values (conversion focus).

* **Secondary:** Current parents seeking school news, event schedules, and contact information.

* **Tertiary:** School staff for content management and updates.

## 2. Branding & Design Guide

### 2.1. Brand Identity

The website must align visually with the "Wonders Kiddies Foundation Schools" brand, focusing on bright, engaging, and professional aesthetics appropriate for an educational institution.

### 2.2. Color Palette (Mandatory Implementation)

The design must heavily utilize the Primary Colours and use the Accent Colours strategically for calls-to-action (CTAs), alerts, and key highlights.

| Name | Hex Code | Usage | 
 | ----- | ----- | ----- | 
| **Lime Green** (Primary) | `#D9EF60` | Light backgrounds, highlights, secondary buttons. | 
| **Dark Green** (Primary) | `#228B22` | Primary buttons, navigation bar, footer, key text elements (the main brand color). | 
| **Red** (Accent) | `#D62828` | Danger states, alerts, or urgent communication. | 
| **Light Blue** (Accent) | `#4EC5F1` | Interactive elements, links, and secondary highlights. | 
| **White** (Neutral) | `#FFFFFF` | Main content background. | 
| **Black** (Neutral) | `#000000` | Primary body text and headings. | 

## 3. Technical Specifications

### 3.1. Required Tech Stack

The entire application must be built exclusively using the following technologies:

* **Backend Framework:** Laravel (latest stable version)

* **Frontend Interactivity:** Full Page Livewire

* **Admin Panel:** Filament PHP (latest stable version, used for all CRUD operations)

* **Styling:** Tailwind CSS (utility-first approach)

* **Client-Side Scripting:** Alpine.js (for simple UI state/dropdowns)

### 3.2. Architecture

* All public-facing pages must be implemented as **Full Page Livewire Components**.

* All backend data management (models, migrations, relationships) must be fully compatible with, and managed through, **Filament Resources and Pages**.

### 3.3. Database & Seeding (New Requirement)

* **Seeder Requirement:** A comprehensive `DatabaseSeeder` must be provided. This seeder must utilize the Laravel Faker library to populate all required tables (`Users`, `Posts`, `Staff`, `GalleryImages`, `Inquiries`, etc.) with mock, descriptive data.

* **Testing Data:** The seeded data must be sufficient to fully test all frontend public pages (ensuring listings like News and Gallery pages are populated) and all Filament Resources without requiring manual data entry post-installation.

## 4. Minimum Viable Product (MVP) Feature Breakdown

### 4.1. Public Facing Pages (Frontend)

All pages must be fully responsive and utilize the defined color palette.

| Page Name | Type | Key Features & Requirements | 
 | ----- | ----- | ----- | 
| **Home Page** | Livewire Component | 1\. **Hero Section:** Large banner with a strong Call-to-Action (e.g., "Enrol Now" button using Dark Green). 2. **Welcome Message:** A brief, compelling message from the Head of School. 3. **Quick Links:** Cards linking to Admissions, Academics, and Contact pages. 4. **Latest News Preview:** Display the three most recent News/Event posts. | 
| **About Us** | Livewire Component | 1\. **Mission, Vision & Values:** Clear display of the school's core principles. 2. **School History:** A brief timeline or summary of the school's journey. 3. **Meet the Leadership:** Profiles of the Head of School and key leaders (Pulled from Filament). | 
| **Academics / Curriculum** | Livewire Component | 1\. **Curriculum Overview:** Description of the educational approach (e.g., early years, primary level structure). 2. **Classes/Levels:** List of available classes (e.g., Nursery, KG1, Primary 1-6). 3. **Subject Highlights:** Brief sections detailing key subjects (Literacy, Numeracy, Science). | 
| **Admissions** | Livewire Component | 1\. **Step-by-Step Process:** Clear outline of the application process. 2. **Fee Schedule:** A simple, legible table of current school fees (managed via Filament). 3. **Inquiry Form:** A simple form (Name, Email, Phone, Child's Age) that submits data to a dedicated table (managed via Filament). | 
| **News & Events** | Livewire Component | 1\. **Listing Page:** Display all news/event posts with pagination. Each item must show title, date, and a brief excerpt. 2. **Detail Page:** A full page to view a single news item, including the full body text and associated media. | 
| **Gallery** | Livewire Component | 1\. **Photo Grid:** A responsive grid displaying school photos. 2. **Categorization:** Ability to filter photos by category (e.g., Sports Day, Graduation, Field Trips - categories managed via Filament). | 
| **Contact Us** | Livewire Component | 1\. **Location:** Embedded Google Map with school location. 2. **Contact Details:** Full address, phone numbers, and email addresses. 3. **Contact Form:** A direct form for general inquiries (similar to Admissions form, submitting to a separate table managed via Filament). | 

### 4.1.1. Dynamic Content Strategy (Updated Requirement)

* **Database-Driven Content:** All user-facing content that is expected to change after launch—including text (welcome messages, mission statements, news bodies), asset URLs (images, PDFs), and data listings (staff, news, gallery)—**must** be stored in the database and pulled as variables into the Livewire Blade components. Hardcoded static text should be limited only to navigational elements or structural labels.

* **Generic Placeholders:** For initial development and testing, all image placeholders in the Livewire Blade components must use generic, dynamic placeholder image URLs (e.g., services like `https://placehold.co/` or `LoremFlickr`) or be pulled from a seeded, generic image in the storage folder. No production-specific images or locally hardcoded image paths should be used in the views.

### 4.2. Admin Panel (Filament PHP)

The Admin Panel must be fully secured and accessible only to authenticated school staff. All models must have corresponding Filament Resources to allow non-developer content management.

| Model / Feature | Description | Required Filament Components | 
 | ----- | ----- | ----- | 
| **Users** | Management of Admin users with basic roles (e.g., Admin, Content Manager). | Filament Resource (User) | 
| **News & Events** | CRUD for school announcements and events. Must include fields for Title, Body (Rich Text Editor), Date, and optional Featured Image. | Filament Resource (Post) | 
| **Admissions Inquiries** | View and manage submissions from the Admissions Inquiry Form. | Filament Resource (Inquiry) | 
| **Contact Submissions** |  andView and manage submissions from the Contact Us Form. | Filament Resource (ContactSubmission) | 
| **Leadership/Staff** | CRUD for staff profiles: Name, Title/Role, Photo, Biography. | Filament Resource (Staff) | 
| **Gallery** | CRUD for managing image uploads. Must include fields for Image file and Category (Select/Relationship field). | Filament Resource (GalleryImage) | 
| **Settings** | A basic Filament Page to manage global variables, such as: School Phone Number, Email, Address, Fee Schedule PDF Link. | Filament Page (Settings) | 

## 5. Non-Functional Requirements (NFRs)

* **Performance:** All Livewire components must be optimized for fast loading times.

* **Security:** Adhere to Laravel's security best practices (CSRF protection, input validation). Filament's access controls must be correctly configured.

* **Code Quality:** Use clear, commented, and standard Laravel/Livewire/Filament code practices.

* **Accessibility:** Adhere to basic WCAG guidelines (clear contrast, keyboard navigation for interactive elements).

## 6. Out of Scope (Future Consideration)

The following features are *not* part of the MVP:

* User accounts for parents/students (parent portal).

* Online payment processing.

* Multi-language support.

* Complex animations or 3D graphics.