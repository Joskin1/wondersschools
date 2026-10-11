<?php

declare(strict_types=1);

namespace App\Services;

use Filament\Facades\Filament;
use Illuminate\Support\Str;

class AdminPermissionRegistry
{
    /**
     * Get base curated admin permissions grouped by module.
     *
     * @return array<string, array{icon: string, items: array<string, array{label: string, description: string}>}>
     */
    public static function getBaseGroups(): array
    {
        return [
            'Academic Management' => [
                'icon' => 'heroicon-o-academic-cap',
                'items' => [
                    'academic_sessions' => [
                        'label' => 'Academic Sessions',
                        'description' => 'Create and manage academic calendar sessions and years',
                    ],
                    'terms' => [
                        'label' => 'Terms',
                        'description' => 'Manage session terms, start/end dates, and active term',
                    ],
                    'classrooms' => [
                        'label' => 'Classrooms',
                        'description' => 'Manage class levels (e.g. JSS 1, SS 3) and ordering',
                    ],
                    'class_arms' => [
                        'label' => 'Class Arms',
                        'description' => 'Manage classroom arms/divisions (e.g. Gold, Diamond)',
                    ],
                    'departments' => [
                        'label' => 'Departments',
                        'description' => 'Manage senior secondary departments (Science, Arts, Commercial)',
                    ],
                    'class_groups' => [
                        'label' => 'Class Groups',
                        'description' => 'Group classes into tiers (Junior, Senior, Primary)',
                    ],
                    'subjects' => [
                        'label' => 'Subjects',
                        'description' => 'Manage curriculum subjects, codes, and elective status',
                    ],
                    'class_teachers' => [
                        'label' => 'Class Teachers',
                        'description' => 'Assign class teachers to classrooms, arms, and departments',
                    ],
                    'subject_teachers' => [
                        'label' => 'Subject Teachers',
                        'description' => 'Assign teachers to subjects across classrooms',
                    ],
                ],
            ],
            'Results & Assessment' => [
                'icon' => 'heroicon-o-clipboard-document-check',
                'items' => [
                    'score_heads' => [
                        'label' => 'Score Heads',
                        'description' => 'Configure assessment heads (Test, Exam, Mid-term, Quiz)',
                    ],
                    'class_score_structure' => [
                        'label' => 'Class Score Structure',
                        'description' => 'Configure score allocations and weightings per classroom',
                    ],
                    'enter_scores' => [
                        'label' => 'Enter Scores',
                        'description' => 'Direct access to enter and edit student CA and exam scores',
                    ],
                    'process_class_results' => [
                        'label' => 'Process & Publish Results',
                        'description' => 'Review class results, compute positions, enter comments, and publish',
                    ],
                ],
            ],
            'Lessons & Curriculum' => [
                'icon' => 'heroicon-o-book-open',
                'items' => [
                    'lesson_notes' => [
                        'label' => 'Lesson Notes',
                        'description' => 'Review, approve, and give feedback on teacher lesson notes',
                    ],
                    'submission_windows' => [
                        'label' => 'Submission Windows',
                        'description' => 'Configure submission windows and deadlines for lesson notes',
                    ],
                ],
            ],
            'User Management' => [
                'icon' => 'heroicon-o-users',
                'items' => [
                    'students' => [
                        'label' => 'Students',
                        'description' => 'Manage student admissions, profiles, and enrollments',
                    ],
                    'users' => [
                        'label' => 'User Accounts',
                        'description' => 'Manage teacher and staff accounts, passwords, and portal access',
                    ],
                ],
            ],
            'Website & Public Info' => [
                'icon' => 'heroicon-o-globe-alt',
                'items' => [
                    'frontend_content' => [
                        'label' => 'Website Content',
                        'description' => 'Edit public landing page content, hero banner, and about text',
                    ],
                    'posts' => [
                        'label' => 'News & Blog Posts',
                        'description' => 'Publish school news articles, announcements, and blog posts',
                    ],
                    'gallery_images' => [
                        'label' => 'Gallery Images',
                        'description' => 'Upload and organize photo gallery albums and event pictures',
                    ],
                    'contact_submissions' => [
                        'label' => 'Contact Submissions',
                        'description' => 'View messages received from website visitors',
                    ],
                    'inquiries' => [
                        'label' => 'Inquiries & Admissions',
                        'description' => 'View prospective student and admission inquiries',
                    ],
                ],
            ],
            'System & Configuration' => [
                'icon' => 'heroicon-o-cog-6-tooth',
                'items' => [
                    'settings' => [
                        'label' => 'School Settings',
                        'description' => 'Configure school branding, logo, colors, contact info, and features',
                    ],
                    'system_logs' => [
                        'label' => 'System Logs',
                        'description' => 'Inspect application logs, errors, and system events',
                    ],
                    'release_notes' => [
                        'label' => 'Release Notes',
                        'description' => 'View platform changelog and new release updates',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get all admin permissions grouped by module with dynamic discovery of newly registered resources.
     *
     * @return array<string, array{icon: string, items: array<string, array{label: string, description: string}>}>
     */
    public static function getPermissionsGrouped(): array
    {
        $groups = static::getBaseGroups();

        try {
            if (class_exists(Filament::class)) {
                $panel = Filament::getPanel('admin');
                if ($panel) {
                    $existingKeys = [];
                    foreach ($groups as $group) {
                        foreach (array_keys($group['items']) as $k) {
                            $existingKeys[] = $k;
                        }
                    }

                    $resources = $panel->getResources();
                    foreach ($resources as $resourceClass) {
                        if (! is_string($resourceClass) || ! class_exists($resourceClass)) {
                            continue;
                        }

                        // Skip hidden staff resource
                        if ($resourceClass === \App\Filament\Resources\StaffResource::class) {
                            continue;
                        }

                        $slug = static::inferResourceKey($resourceClass);
                        if (! in_array($slug, $existingKeys, true)) {
                            $navGroup = method_exists($resourceClass, 'getNavigationGroup')
                                ? ((string) $resourceClass::getNavigationGroup() ?: 'Additional Resources')
                                : 'Additional Resources';

                            if (! isset($groups[$navGroup])) {
                                $groups[$navGroup] = [
                                    'icon' => 'heroicon-o-cube',
                                    'items' => [],
                                ];
                            }

                            $label = method_exists($resourceClass, 'getNavigationLabel')
                                ? ((string) $resourceClass::getNavigationLabel() ?: class_basename($resourceClass))
                                : class_basename($resourceClass);

                            $groups[$navGroup]['items'][$slug] = [
                                'label' => $label,
                                'description' => "Manage {$label} records",
                            ];
                            $existingKeys[] = $slug;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Gracefully ignore during early boot or console commands without Filament active
        }

        return $groups;
    }

    /**
     * Infer permission slug from resource class name.
     */
    public static function inferResourceKey(string $resourceClass): string
    {
        $basename = class_basename($resourceClass);
        $clean = (string) preg_replace('/Resource$/', '', $basename);

        return Str::snake(Str::plural($clean));
    }

    /**
     * Check if a permission is a custom page rather than an Eloquent resource.
     */
    public static function isPageOnly(string $key): bool
    {
        return in_array($key, [
            'class_score_structure',
            'enter_scores',
            'process_class_results',
            'settings',
            'system_logs',
            'release_notes',
        ], true);
    }

    /**
     * Get supported granular actions for a resource.
     *
     * @return array<string, string>
     */
    public static function getActionsForResource(string $resourceKey): array
    {
        if (static::isPageOnly($resourceKey)) {
            return [];
        }

        return [
            'view' => 'View (List & Details)',
            'create' => 'Create New',
            'update' => 'Edit & Update',
            'delete' => 'Delete',
        ];
    }

    /**
     * Get all registered permission keys.
     *
     * @return list<string>
     */
    public static function getAllKeys(): array
    {
        $keys = [];
        foreach (static::getPermissionsGrouped() as $group) {
            foreach ($group['items'] as $key => $item) {
                $keys[] = $key;
                if (! static::isPageOnly($key)) {
                    foreach (array_keys(static::getActionsForResource($key)) as $action) {
                        $keys[] = "{$key}.{$action}";
                    }
                }
            }
        }

        return array_values(array_unique($keys));
    }

    /**
     * Check if a resource is fully granted for the given permissions list.
     */
    public static function isResourceFullyGranted(string $resourceKey, array $userPermissions): bool
    {
        if (in_array('*', $userPermissions, true) || in_array($resourceKey, $userPermissions, true) || in_array("{$resourceKey}.*", $userPermissions, true)) {
            return true;
        }

        $actions = array_keys(static::getActionsForResource($resourceKey));
        if (empty($actions)) {
            return false;
        }

        foreach ($actions as $action) {
            if (! in_array("{$resourceKey}.{$action}", $userPermissions, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get predefined role presets (convenient templates).
     *
     * @return array<string, array{name: string, designation: string, description: string, pages: list<string>}>
     */
    public static function getPresets(): array
    {
        return [
            'bursar' => [
                'name' => 'Bursar / Accounts',
                'designation' => 'Bursar',
                'description' => 'Access to view student records, inquiries, and public messages without altering academic records or student data',
                'pages' => [
                    'students.view',
                    'inquiries',
                    'contact_submissions',
                ],
            ],
            'exam_officer' => [
                'name' => 'Exam Officer',
                'designation' => 'Exam Officer',
                'description' => 'Full access to examinations, score entry, result processing, academic structure, and student records',
                'pages' => [
                    'enter_scores',
                    'process_class_results',
                    'score_heads',
                    'class_score_structure',
                    'academic_sessions',
                    'terms',
                    'classrooms',
                    'class_arms',
                    'departments',
                    'subjects',
                    'students.view',
                ],
            ],
            'vp_academic' => [
                'name' => 'Vice Principal (Academics)',
                'designation' => 'Vice Principal (Academics)',
                'description' => 'Full control over academic sessions, classrooms, subjects, teachers, lesson notes, and student results',
                'pages' => [
                    'academic_sessions',
                    'terms',
                    'classrooms',
                    'class_arms',
                    'departments',
                    'class_groups',
                    'subjects',
                    'class_teachers',
                    'subject_teachers',
                    'score_heads',
                    'class_score_structure',
                    'enter_scores',
                    'process_class_results',
                    'lesson_notes',
                    'submission_windows',
                    'students.view',
                ],
            ],
            'front_desk' => [
                'name' => 'Front Desk / Secretary',
                'designation' => 'Front Desk Officer',
                'description' => 'Admissions, student directory (view only), public website inquiries, news posts, and media gallery',
                'pages' => [
                    'students.view',
                    'inquiries',
                    'contact_submissions',
                    'posts',
                    'gallery_images',
                ],
            ],
        ];
    }
}
