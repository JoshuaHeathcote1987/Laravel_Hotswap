<?php

namespace Placeholder\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class PlaceholderController extends Controller
{
    public function index()
    {
        return Inertia::render('placeholder/placeholder/index');
    }
}