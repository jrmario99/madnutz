<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            Category::orderBy('sort_order')->get()
        );
    }

    public function show(string $slug)
    {
        return response()->json(
            Category::where('slug', $slug)->firstOrFail()
        );
    }
}
