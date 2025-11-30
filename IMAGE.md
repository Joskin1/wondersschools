
---

# 🖼️ WKFS Website Image and Asset Strategy

**Project Name:** Wonder Kiddies Foundation Schools (WKFS) Website
**Date:** November 29, 2025
**Version:** 1.0 (Image Strategy)
**Owner:** Akinbomi OLuwadamilare
**Your Role:** Developer

## 1. 🎯 Goal & Core Requirements

The goal is to establish a secure, performant, and dynamic system for managing all visual assets, ensuring the website is fully populated with high-quality, relevant images upon initial deployment (including production) for a visually rich experience.

### 1.1. Core Requirements

1.  **Production Readiness:** All necessary images must be present in the **database** and accessible via the **public file system** upon production deployment without manual intervention.
2.  **Seeding Integration:** The `DatabaseSeeder` must utilize the included local images to populate all image-related database tables (`Posts`, `Staff`, `GalleryImages`, etc.).
3.  **Dynamic Display:** All image paths used on the frontend must be dynamically pulled from the database for easy management via **Filament**.
4.  **Performance:** Implement best practices for image loading (**optimization, responsive sizing**).

---

## 2. 🛠️ Technical Implementation (The Foundation)

### 2.1. Asset Storage & Access

| Requirement | Implementation Strategy |
| :--- | :--- |
| **Local Development Assets** | Store all raw images provided by the client in a dedicated directory, e.g., `/resources/images/seeds`. **These are the source images for the seeder.** |
| **Public Production Assets** | Utilize the **Laravel Storage component** and the `public` disk. Images should be stored in `/storage/app/public/images` (or similar) and symlinked to `/public/storage` for web access. |
| **Seeding Mechanism** | The **`DatabaseSeeder`** must use `Storage::put()` or similar methods to **copy** the images from the `/resources/images/seeds` folder to the `/storage/app/public/images` path, and then save the resulting public path to the relevant database columns. **This ensures the images are available on Production.** |

### 2.2. Image Optimization & Responsiveness

1.  **Optimization:** Implement a suitable package (or custom pipeline) to ensure all images are compressed and served in modern formats (like WebP) for performance.
2.  **Responsiveness:** Utilize **Tailwind CSS's responsive utilities** and the `srcset` or `<picture>` element to serve appropriately sized images across mobile, tablet, and desktop views.
3.  **Loading:** Use **Lazy Loading** for all images that are below the fold (not in the initial viewport).

---

## 3. 📝 Image Content & Placement Strategy

The strategy incorporates both provided (client) and AI-generated images to ensure every required section has a high-quality visual asset.

### 3.1. Provided Images (Seeding Priority)

The seeder must prioritize the client's provided images based on their file names for the most relevant placements.

| Provided Image Name Hint | WKFS Page/Component | Required Seeding Action |
| :--- | :--- | :--- |
| `logo.png` / `logo.jpg` | **Header, Footer, Favicon** | Configured as a global setting (`Filament Page (Settings)`) and used in the main Blade layout. |
| `head-of-school.jpg` / `leadership.jpg` | **Home Page - Meet the Leadership** | Seeding the **Staff** table for the Head of School profile. |
| `class-1-math.jpg`, `class-2-art.jpg` (or similar) | **Gallery Page** | Seeding the **GalleryImage** table, categorized appropriately. |
| `hero-1.jpg`, `hero-2.jpg`, `hero-3.jpg` | **Home Page - Hero Section** | Seeding a dedicated **HeroSlider** table or using images tagged with a `Hero` category in the `GalleryImage` table. |

### 3.2. AI-Generated Image Requirements (Placeholder/Stock)

The following assets must be created or sourced if client images are insufficient to ensure a visually rich and compelling site on launch.

| Target Component | Required Image Content (for AI/Stock Generation) | Image Tag (for Reference) |
| :--- | :--- | :--- |
| **Home Hero Slider (x3)** | Diverse, high-quality, and aspirational photos of happy children (4-12 years old) learning and engaging in activities in a bright, modern school environment. |  |
| **Why WKFS? Section** | An image representing the school's "foundation" philosophy, e.g., children focused on foundational block play or collaborative activity. |  |
| **Academics Page** | A photo showing **STEM learning**—a small group of children engaged in a simple science experiment or coding activity. |  |
| **About Us Page (Mission)** | A clean, professional shot of the **school exterior/campus** that conveys security and quality. |  |

---

## 4. 📝 Content Flow Feature Expansion (Images)

### 4.1. Home Page Implementation

| Section Title | Image Implementation Detail |
| :--- | :--- |
| **Hero Section** | Must implement a **responsive image slider** using Livewire/Alpine.js. The slider must pull at least **three images** from the database and cycle them automatically. |
| **Why WKFS?** | Placement of the **Foundation/Block Play** image (see 3.2). |
| **School Life** | Each of the **three most recent News/Event posts** must display its `Featured Image` (from the database) as a thumbnail. |
| **Meet the Leadership** | Each staff profile must display the staff member's **Profile Photo** (from the database) with a square or circular crop. |

### 4.2. Gallery Page Implementation

* The **Gallery** must display a full grid of images retrieved from the `GalleryImage` database table.
* Implement an accessible **Lightbox/Modal** feature using Alpine.js for full-screen viewing of the images upon click.
* The filter function must be a **Livewire component** to filter images by **Category** without a full page reload.

---

## 5. ⚙️ Admin Panel (Filament PHP Resources)

The Admin Panel must facilitate the easy upload and management of all these assets by school staff.

| Filament Component | Required Image Field Type | Notes |
| :--- | :--- | :--- |
| **Post Resource** | `SpatieMediaLibrary` or `FileUpload` Field | Must support one primary Featured Image. |
| **Staff Resource** | `SpatieMediaLibrary` or `FileUpload` Field | Must support one Profile Photo. |
| **GalleryImage Resource** | `SpatieMediaLibrary` or `FileUpload` Field | Must support one image upload and linkage to a **Category**. |
| **Settings Page** | `FileUpload` Field | For Logo/Favicon and other static assets. |

---
