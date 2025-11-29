<?php

use App\Livewire\Home;
use App\Livewire\About;
use App\Livewire\Academics;
use App\Livewire\Admissions;
use App\Livewire\News;
use App\Livewire\Post;
use App\Livewire\Gallery;
use App\Livewire\Contact;
use Illuminate\Support\Facades\Route;

Route::get('/', Home::class)->name('home');
Route::get('/about-us', About::class)->name('about');
Route::get('/academics', Academics::class)->name('academics');
Route::get('/admissions', Admissions::class)->name('admissions');
Route::get('/news', News::class)->name('news');
Route::get('/news/{post:slug}', Post::class)->name('post');
Route::get('/gallery', Gallery::class)->name('gallery');
Route::get('/contact-us', Contact::class)->name('contact');
