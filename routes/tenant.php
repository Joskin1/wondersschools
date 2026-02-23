<?php

declare(strict_types=1);

use App\Http\Controllers\TeacherRegistrationController;
use App\Livewire\About;
use App\Livewire\Academics;
use App\Livewire\Admissions;
use App\Livewire\Contact;
use App\Livewire\Gallery;
use App\Livewire\Home;
use App\Livewire\News;
use App\Livewire\Post;
use App\Livewire\RegisterStudent;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
| These routes are registered by TenancyServiceProvider with
| InitializeTenancyByDomain + PreventAccessFromCentralDomains middleware.
| They are available on every tenant domain (e.g. royal-academy.wonders.test).
|
*/

Route::get('/', Home::class)->name('home');
Route::get('/about-us', About::class)->name('about');
Route::get('/academics', Academics::class)->name('academics');
Route::get('/admissions', Admissions::class)->name('admissions');
Route::get('/news', News::class)->name('news');
Route::get('/news/{post:slug}', Post::class)->name('post');
Route::get('/gallery', Gallery::class)->name('gallery');
Route::get('/contact-us', Contact::class)->name('contact');

// Teacher Registration
Route::get('/teacher/register/{token}', [TeacherRegistrationController::class, 'show'])
    ->name('teacher.register');

// Student Registration
Route::get('/register/student/{slug}', RegisterStudent::class)
    ->name('student.register');
