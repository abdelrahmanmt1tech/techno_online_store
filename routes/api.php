<?php

use App\Http\Controllers\Api\Central\BlogController;
use App\Http\Controllers\Api\Central\ContactController;
use App\Http\Controllers\Api\Central\CountryController;
use App\Http\Controllers\Api\Central\CurrencyController;
use App\Http\Controllers\Api\Central\HomeController;
use App\Http\Controllers\Api\Central\SettingsController;
use App\Http\Controllers\Api\Central\TenantController;
use Illuminate\Support\Facades\Route;

Route::get('home', [HomeController::class, 'getHomeData']);
Route::get('themes', [HomeController::class, 'getThemes']);
Route::get('categories', [HomeController::class, 'getCategories']);
Route::get('footer', [HomeController::class, 'getFooter']);
Route::post('contact', [ContactController::class, 'store']);
Route::get('terms', [HomeController::class, 'getTerms']);
Route::get('privacy', [HomeController::class, 'getPrivacy']);
Route::get('blogs', [BlogController::class, 'index']);
Route::get('blogs/categories', [BlogController::class, 'getCategories']);
Route::get('blogs/{slug}', [BlogController::class, 'show']);
Route::get('settings', [SettingsController::class, 'index']);
Route::get('countries', [CountryController::class, 'index']);
Route::get('currencies', [CurrencyController::class, 'index']);
Route::post('tenants', [TenantController::class, 'store']);
