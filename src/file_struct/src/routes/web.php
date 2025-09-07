<?php

use Illuminate\Support\Facades\Route;
use Placeholder\App\Http\Controllers\PlaceholderController;

Route::get('/placeholder', [PlaceholderController::class, 'index']);