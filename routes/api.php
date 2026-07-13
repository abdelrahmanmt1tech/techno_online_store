<?php

use App\Http\Controllers\Api\Front\BlogController;
use App\Http\Controllers\Api\Front\ContactController;
use App\Http\Controllers\Api\Front\HomeController;
use App\Http\Controllers\Api\Front\SettingsController;
use App\Http\Controllers\Api\Front\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('home', [HomeController::class, 'getHomeData']);
Route::get('themes', [HomeController::class, 'getThemes']);
Route::get('categories', [HomeController::class, 'getCategories']);
Route::get('footer', [HomeController::class, 'getFooter']);
Route::post('contact', [ContactController::class, 'store']);
Route::get('terms', [HomeController::class, 'getTerms']);
Route::get('privacy', [HomeController::class, 'getPrivacy']);
Route::get('blogs', [BlogController::class, 'index'])->name('api.blogs.index');
Route::get('blogs/{slug}', [BlogController::class, 'show'])->name('api.blogs.show');
Route::get('settings', [SettingsController::class, 'index']);
Route::post('tenants', [TenantController::class, 'store'])->name('api.tenants.store');
