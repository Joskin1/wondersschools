<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Post;
use App\Models\Staff;
use App\Models\GalleryImage;
use App\Models\Setting;
use App\Models\Inquiry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin User
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@wkfs.com',
            'password' => bcrypt('password'),
        ]);

        // Staff - Leadership
        Staff::factory()->create([
            'name' => 'Mrs. Jane Doe',
            'role' => 'Head of School',
            'bio' => 'Mrs. Doe has over 20 years of experience in early childhood education. She is passionate about creating a nurturing environment for all children.',
            'image' => 'https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        ]);

        Staff::factory()->create([
            'name' => 'Mr. John Smith',
            'role' => 'Head of Academics',
            'bio' => 'Mr. Smith ensures our curriculum meets international standards and challenges every student to reach their full potential.',
            'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
        ]);

        Staff::factory(4)->create();

        // News & Events
        Post::factory()->create([
            'title' => 'Welcome to the New Academic Session',
            'body' => 'We are thrilled to welcome all our students back to school! This term promises to be full of exciting learning opportunities and events.',
            'published_at' => now()->subDays(2),
            'image' => 'https://images.unsplash.com/photo-1509062522246-3755977927d7?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80',
        ]);

        Post::factory()->create([
            'title' => 'Sports Day 2025 Announced',
            'body' => 'Get ready for our annual Sports Day! Join us for a day of fun, competition, and school spirit. Parents are welcome to attend.',
            'published_at' => now()->subDays(5),
            'image' => 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80',
        ]);

        Post::factory()->create([
            'title' => 'Science Fair Winners',
            'body' => 'Congratulations to all the participants of this year\'s Science Fair. The creativity and innovation shown by our students were truly impressive.',
            'published_at' => now()->subDays(10),
            'image' => 'https://images.unsplash.com/photo-1564325724739-bae0bd08762c?ixlib=rb-1.2.1&auto=format&fit=crop&w=1600&q=80',
        ]);

        Post::factory(5)->create();

        // Gallery
        $categories = ['Sports Day', 'Graduation', 'Field Trips', 'Classroom Activities', 'Art Exhibition'];
        foreach ($categories as $category) {
            GalleryImage::factory(3)->create(['category' => $category]);
        }

        // Inquiries & Contact Submissions
        Inquiry::factory(5)->create();
        \App\Models\ContactSubmission::factory(5)->create();

        // Settings
        $settings = [
            'school_name' => 'Wonders Kiddies Foundation Schools',
            'school_email' => 'info@wkfs.com',
            'school_phone' => '+234 800 123 4567',
            'school_address' => '123 Education Street, Lagos, Nigeria',
            'fee_schedule_link' => '#',
            'facebook_link' => 'https://facebook.com',
            'instagram_link' => 'https://instagram.com',
            'twitter_link' => 'https://twitter.com',
        ];

        foreach ($settings as $key => $value) {
            \App\Models\Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
