That's a very clear implementation plan\! To create a powerful prompt for an AI to review your existing code and ensure the new features are correctly integrated, you should combine the **Implementation Plan** details with your new requirements, focusing on the **Teacher** and **Student** roles.

Here is a comprehensive prompt you can use. You will insert your existing code files (like models, panel configurations, policy files, etc.) into the designated section of the actual prompt when you use it.

## 📝 Comprehensive AI Code Review Prompt

````
**Goal: Code Review & Integration for School Management System (Laravel, Tailwind, Filament 4)**

I am implementing a Student Portal and Class Teacher functionality based on the detailed plan below. I need you to act as a senior Laravel/Filament architect. Review the provided code snippets and ensure they correctly implement the system described, paying special attention to security, Filament architecture, and role separation.

---

### 1. Existing Implementation Plan (Review Context)

**Student Portal & Result Printing**

* **Authentication Strategy (Student):** Separate Filament Panel (`/student`) using `admission_number` and `password`.
* **Database Changes:** `students` table has `admission_number` (string, unique) and `password` (string). `Student` model implements `Authenticatable`.
* **Authentication Config:** `auth.php` is configured with `student` guard and provider.
* **Student Panel:** New panel created (`php artisan filament:panel student`) using the `student` guard.
* **Result View:** `ViewResult` page for students to list, view, and print their Report Cards using CSS `@media print`.

---

### 2. New Feature Requirements & Role Separation

**A. Class Teacher Functionality (New Focus)**

1.  **Staff/Teacher Assignment:** An **Admin** must be able to assign a registered Staff/User as a **Class Teacher** to a specific `Class`. (e.g., A `User` record is linked to a `Class` record).
2.  **Teacher Access & Scope:** A **Class Teacher** logging into the **Admin Filament Panel** (or a dedicated Teacher Panel, if applicable, but for now assume Admin Panel access with permissions) must only be able to:
    * View a list of **Students** belonging *only* to their assigned class(es).
    * View the detailed profile of those students.
3.  **Score Upload/Scoring:** A **Class Teacher** must be able to:
    * Input/Give **Scores** for students in their assigned class(es).
    * The scoring must be based on the **Evaluation Methods** (e.g., 'Score Head') defined by the Admin (e.g., *Test 1*, *Exam*, *Homework*).
4.  **Automatic Calculation:** The system must automatically perform all necessary calculations (e.g., total score, grade, position) when scores are submitted/updated.

**B. Student Portal/Result (Confirmation)**

1.  **Login:** Student login must strictly require `admission_number` and `password` and route to the `/student` panel.
2.  **Result View:** Students must be able to view and print/download their automatically calculated results.

---

### 3. Task for AI

1.  **Review & Validate:** Check the provided code snippets against all requirements (1, 2A, 2B).
2.  **Identify Gaps/Errors:** Highlight any missing configurations, incorrect Filament logic, insecure data exposure, or incorrect model relationships.
3.  **Provide Corrective/New Code:** For any identified gaps, provide the corrected or entirely new code blocks (e.g., the policy, the relationship in a model, the correct panel configuration, or the logic in a Filament Resource/Page) to achieve the required functionality.
4.  **Focus Area:** Specifically, ensure the mechanism for linking a `User` (Staff/Teacher) to a `Class` and the **Policy** that restricts a teacher's view/edit scope to *only* their assigned students is robust and correctly implemented in Filament.

---

### 4. Code Snippets to Review

*[**IMPORTANT:** Replace this block with your actual code. If you cannot share all files, share the most relevant ones: `Student.php` model, `User.php` model (Staff/Teacher), the Teacher/Admin Panel configuration, the Student Resource/Policy, and any relevant database migration files.]*

```php
// Your existing Student.php model
// Your existing User.php model
// Your existing database migrations (students, classes, class_teacher_assignment, scores)
// Your Filament StudentPanelProvider.php
// Your Filament StudentResource.php
// Your ClassPolicy.php or StudentPolicy.php (if any)
````

```
```